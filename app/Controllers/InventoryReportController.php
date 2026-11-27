<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InventoryReportController extends BaseController
{
    public function __construct(Container $container, private CheckoutModel $model)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $export = ($query['export'] ?? '') === 'csv';
        $category = $query['category'] ?? null;
        $lowThreshold = (int) ($query['low_threshold'] ?? 5);

        $inventory = $this->model->inventoryReport($category);

        // Classify stock levels with red / yellow / green
        foreach ($inventory as &$item) {
            $qty = (int) $item['in_stock'];
            if ($qty <= $lowThreshold) {
                $item['stock_level'] = 'low';
                $item['stock_color'] = 'danger';    // Bootstrap bg-danger = red
                $item['stock_label_color'] = 'red';
            } elseif ($qty <= $lowThreshold * 2) {
                $item['stock_level'] = 'moderate';
                $item['stock_color'] = 'warning';   // Bootstrap bg-warning = yellow
                $item['stock_label_color'] = 'yellow';
            } else {
                $item['stock_level'] = 'ok';
                $item['stock_color'] = 'success';   // Bootstrap bg-success = green
                $item['stock_label_color'] = 'green';
            }
        }
        unset($item);

        $categories = $this->model->getProductCategories();

        $totalProducts = count($inventory);
        $lowStockCount = 0;
        $totalInStock = 0;
        $totalValue = 0.0;
        foreach ($inventory as $item) {
            $totalInStock += (int) $item['in_stock'];
            $totalValue += (float) $item['price'] * (int) $item['in_stock'];
            if ($item['stock_level'] === 'low') {
                $lowStockCount++;
            }
        }

        if ($export) {
            return $this->exportInventoryCsv($response, $inventory);
        }

        return $this->render($response, 'admin/inventory_report.php', [
            'inventory' => $inventory,
            'categories' => $categories,
            'selectedCategory' => $category,
            'lowThreshold' => $lowThreshold,
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockCount,
            'totalInStock' => $totalInStock,
            'totalValue' => $totalValue,
        ]);
    }

    private function exportInventoryCsv(Response $response, array $inventory): Response
    {
        $response = $response->withHeader('Content-Type', 'text/csv; charset=utf-8');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="inventory_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Product ID', 'Product Name', 'Category', 'Price', 'UPC',
            'Available Quantity', 'Stock Level', 'Total Received', 'Total Sold',
            'Last Restocked', 'EPCs Registered',
        ]);

        foreach ($inventory as $item) {
            fputcsv($output, [
                $item['id'],
                $item['name'],
                $item['category'],
                '$' . number_format((float) $item['price'], 2),
                $item['upc'],
                $item['in_stock'],
                ucfirst($item['stock_level']),
                $item['received_total'],
                $item['sold_total'],
                $item['last_restocked'] ?? 'Never',
                $item['epcs_registered'],
            ]);
        }

        fclose($output);
        return $response;
    }
}