<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use App\Helpers\Core\FlashMessage;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\RfidScannerState;

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
            $csrf = (string) ($data['csrf'] ?? '');

            if ($csrf === '' ||
                !hash_equals((string) ($_SESSION['product_csrf'] ?? ''), $csrf)) {
                throw new \RuntimeException('Invalid product form.');
            }

            $productId = $this->model->createProduct([
                'name' => trim((string) ($data['name'] ?? '')),
                'category' => trim((string) ($data['category'] ?? '')),
                'price' => (float) ($data['price'] ?? 0),
                'upc' => trim((string) ($data['upc'] ?? '')),
                'producer_company' => trim((string) ($data['producer_company'] ?? '')),
            ]);

            $stock = max(0, (int) ($data['stock_quantity'] ?? 0));

            if ($stock > 0) {
                $this->model->addStock($productId, $stock);
            }

            FlashMessage::success('Product added.');

        } catch (\Throwable $e) {
            error_log('Create product error: ' . $e->getMessage());
            FlashMessage::error('Could not add product: ' . $e->getMessage());
        }

        return $response
            ->withStatus(303)
            ->withHeader('Location', BASE_URL . 'product');
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
            'epcCount' => $this->model->countProductEpcs($id),
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

    // Register scanned RFID tags to an existing product.
    public function scanEpcs(Request $request, Response $response, array $args = []): Response
    {
        $expectedKey = getenv('CHECKOUT_SCAN_KEY') ?: '';
        $providedKey = $request->getHeaderLine('X-Checkout-Key');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            $response->getBody()->write('Invalid scanner key.');
            return $response->withStatus(403);
        }

        $productId = (int) ($args['id'] ?? 0);

        if ($productId <= 0 || !$this->model->getProduct($productId)) {
            $response->getBody()->write('Product not found.');
            return $response->withStatus(404);
        }

        $data = json_decode((string) $request->getBody(), true);
        $epcs = $data['epcs'] ?? null;

        if (!is_array($epcs) || empty($epcs) || count($epcs) > 500) {
            $response->getBody()->write('Invalid EPC list.');
            return $response->withStatus(400);
        }

        foreach ($epcs as $epc) {
            if (!is_string($epc)) {
                $response->getBody()->write('Invalid EPC value.');
                return $response->withStatus(400);
            }
        }

        try {
            $result = $this->model->addEpcsToProduct(
                $productId,
                implode(',', $epcs)
            );

            $payload = [
                'product_id' => $productId,
                'added' => count($result['added']),
                'ignored' => count($result['ignored']),
                'registered_total' => $this->model->countProductEpcs($productId)
            ];

            $response->getBody()->write(json_encode($payload));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            error_log('RFID registration error: ' . $e->getMessage());
            $response->getBody()->write('Could not register RFID tags.');
            return $response->withStatus(500);
        }
    }


        
    public function activateProductScanner(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $data = (array) ($request->getParsedBody() ?? []);
        $csrf = (string) ($data['csrf'] ?? '');

        if (
            $csrf === '' ||
            !hash_equals((string) ($_SESSION['product_csrf'] ?? ''), $csrf)
        ) {
            return $response->withStatus(403);
        }

        if ($id <= 0 || !$this->model->getProduct($id)) {
            return $response->withStatus(404);
        }

        RfidScannerState::activate('product', (string) $id);

        return $response->withStatus(204);
    }


    // Automatically select the scanner for checkout.
    public function activateCheckoutScanner(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $token = (string) ($data['token'] ?? '');

        if (
            $token === '' ||
            !hash_equals((string) ($_SESSION['checkout_scanner_token'] ?? ''), $token)
        ) {
            return $response->withStatus(403);
        }

        RfidScannerState::activate('checkout');

        return $response->withStatus(204);
    }

    // Receive RFID scans from the Chafon reader.
    public function receiveRfidScan(Request $request, Response $response): Response
    {
        $expectedKey = getenv('CHECKOUT_SCAN_KEY') ?: '';
        $providedKey = $request->getHeaderLine('X-Checkout-Key');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            return $response->withStatus(403);
        }

        $data = json_decode((string) $request->getBody(), true);
        $epcs = $data['epcs'] ?? null;

        if (
            !is_array($epcs) ||
            count($epcs) === 0 ||
            count($epcs) > 500
        ) {
            return $response->withStatus(400);
        }

        foreach ($epcs as $epc) {
            if (!is_string($epc)) {
                return $response->withStatus(400);
            }
        }

        try {
            $state = RfidScannerState::current();

            if ($state['mode'] === 'checkout') {
                $result = $this->model->addEpcs($epcs);

            } elseif ($state['mode'] === 'product') {
                $productId = (int) ($state['batch'] ?? 0);

                if ($productId <= 0 || !$this->model->getProduct($productId)) {
                    return $response->withStatus(409);
                }

                $registration = $this->model->addEpcsToProduct(
                    $productId,
                    implode(',', $epcs)
                );

                $result = [
                    'mode' => 'product',
                    'product_id' => $productId,
                    'added' => count($registration['added']),
                    'ignored' => count($registration['ignored']),
                    'registered_total' => $this->model->countProductEpcs($productId)
                ];

            } else {
                $result = ['mode' => 'idle'];
            }

            $response->getBody()->write(
                json_encode($result)
            );

            return $response
                ->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            error_log('RFID scan error: ' . $e->getMessage());

            return $response->withStatus(500);
        }
    }

        
    public function pendingRfidCount(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0 || !$this->model->getProduct($id)) {
            return $response->withStatus(404);
        }

        $epcs = $this->model->getProductEpcs($id);

        $response->getBody()->write(json_encode([
            'count' => count($epcs),
            'epcs' => $epcs
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');
    }

    public function clearEpcs(Request $request, Response $response, array $args = []): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $data = (array) ($request->getParsedBody() ?? []);
        $csrf = (string) ($data['csrf'] ?? '');

        if (
            $csrf === '' ||
            !hash_equals((string) ($_SESSION['product_csrf'] ?? ''), $csrf)
        ) {
            return $response->withStatus(403);
        }

        if ($id <= 0 || !$this->model->getProduct($id)) {
            return $response->withStatus(404);
        }

        try {
            RfidScannerState::pauseProduct($id);
            $this->model->clearProductEpcs($id);

            return $response->withStatus(204);
        } catch (\Throwable $e) {
            error_log('Clear EPCs error: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

}