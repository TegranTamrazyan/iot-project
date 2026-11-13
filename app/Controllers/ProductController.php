<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use App\Helpers\Core\FlashMessage;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController extends BaseController
{
    public function __construct(Container $container, private CheckoutModel $model)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'product/index.php', [
            'products' => $this->model->allProducts(),
            'receptions' => $this->model->allReceptions(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        try {
            $productId = $this->model->createProduct([
                'name' => trim((string) ($data['name'] ?? '')),
                'category' => trim((string) ($data['category'] ?? '')),
                'price' => (float) ($data['price'] ?? 0),
                'upc' => trim((string) ($data['upc'] ?? '')),
                'producer_company' => trim((string) ($data['producer_company'] ?? '')),
            ]);
            $stock = (int) ($data['stock_quantity'] ?? 0);
            if ($stock > 0 && $productId > 0) {
                $this->model->addStock($productId, $stock);
            }
            FlashMessage::success('product added');
        } catch (\Throwable $e) {
            error_log('create product error: ' . $e->getMessage());
            FlashMessage::error('could not add product: ' . $e->getMessage());
        }
        return $response->withStatus(302)->withHeader('Location', BASE_URL . 'product');
    }

    public function edit(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $product = $this->model->getProduct($id);
        if (!$product) {
            return $response->withStatus(302)->withHeader('Location', BASE_URL . 'product');
        }
        return $this->render($response, 'product/edit.php', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $data = (array) ($request->getParsedBody() ?? []);
        try {
            $this->model->updateProduct($id, [
                'name' => trim((string) ($data['name'] ?? '')),
                'category' => trim((string) ($data['category'] ?? '')),
                'price' => (float) ($data['price'] ?? 0),
                'upc' => trim((string) ($data['upc'] ?? '')),
                'producer_company' => trim((string) ($data['producer_company'] ?? '')),
                'active' => isset($data['active']),
            ]);
            $stock = (int) ($data['stock_quantity'] ?? 0);
            if ($stock > 0) {
                $this->model->addStock($id, $stock);
            }
            $epcList = trim((string) ($data['epcs'] ?? ''));
            if ($epcList !== '') {
                $result = $this->model->addEpcsToProduct($id, $epcList);
                foreach ($result['added'] as $epc) {
                    FlashMessage::success('added EPC ' . $epc);
                }
                foreach ($result['ignored'] as $epc) {
                    FlashMessage::error('EPC ' . $epc . ' not added');
                }
            }
            FlashMessage::success('product updated');
        } catch (\Throwable $e) {
            error_log('update product error: ' . $e->getMessage());
            FlashMessage::error('could not update product: ' . $e->getMessage());
        }
        return $response->withStatus(302)->withHeader('Location', BASE_URL . 'product');
    }

    public function delete(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);
        try {
            $this->model->deleteProduct($id);
            FlashMessage::success('product deleted');
        } catch (\Throwable $e) {
            FlashMessage::error('could not delete product');
        }
        return $response->withStatus(302)->withHeader('Location', BASE_URL . 'product');
    }

    public function deleteMultiple(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $ids = $data['selected'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as $id) {
            try {
                $this->model->deleteProduct((int) $id);
            } catch (\Throwable $e) {
                // skip products that cannot be deleted
            }
        }
        FlashMessage::success('selected products deleted');
        return $response->withStatus(302)->withHeader('Location', BASE_URL . 'product');
    }
}