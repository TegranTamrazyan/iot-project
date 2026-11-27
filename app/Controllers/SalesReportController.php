<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SalesReportController extends BaseController
{
    public function __construct(Container $container, private CheckoutModel $model)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $export = ($query['export'] ?? '') === 'csv';
        $chartType = $query['chart'] ?? 'line';
        $category = $query['category'] ?? null;

        $hasDateFilters = array_key_exists('from', $query) || array_key_exists('to', $query);
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $fromDate = $hasDateFilters
            ? (is_string($query['from'] ?? null) ? trim($query['from']) : $today)
            : $today;
        $toDate = $hasDateFilters
            ? (is_string($query['to'] ?? null) ? trim($query['to']) : $today)
            : $today;

        $reportError = '';

        $salesData = [];
        $dailyRevenue = [];
        $totals = [
            'totalItems' => 0,
            'totalRevenue' => 0.0,
            'totalOrders' => 0,
        ];
        $topProduct = null;
        $bottomProduct = null;

        if ($reportError === '' && $hasDateFilters) {
            if ($fromDate === '' || $toDate === '') {
                $reportError = 'Select both a start date and an end date.';
            } elseif ($fromDate > $toDate) {
                $reportError = 'Start date must be on or before end date.';
            }
        }

        if ($reportError === '' && $hasDateFilters) {
            $salesData = $this->model->salesReport($fromDate, $toDate, $category);
            $dailyRevenue = $this->model->dailyRevenue($fromDate, $toDate, $category);

            foreach ($salesData as $item) {
                $totals['totalItems'] += (int) $item['quantity_sold'];
                $totals['totalRevenue'] += (float) $item['revenue'];
            }

            $orderIds = [];
            foreach ($salesData as $item) {
                $orderIds[] = (int) $item['order_count'];
            }
            $totals['totalOrders'] = array_sum($orderIds);

            if (!empty($salesData)) {
                $topProduct = $salesData[0];
                $bottomProduct = end($salesData);
            }
        }

        $categories = $this->model->getProductCategories();

        if ($export) {
            return $this->exportSalesCsv($response, $salesData, $fromDate, $toDate, $totals);
        }

        return $this->render($response, 'admin/sales_report.php', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'reportError' => $reportError,
            'salesData' => $salesData,
            'dailyRevenue' => $dailyRevenue,
            'totals' => $totals,
            'topProduct' => $topProduct,
            'bottomProduct' => $bottomProduct,
            'categories' => $categories,
            'selectedCategory' => $category,
            'chartType' => $chartType,
        ]);
    }

    private function exportSalesCsv(
        Response $response,
        array $salesData,
        string $fromDate,
        string $toDate,
        array $totals
    ): Response {
        $response = $response->withHeader('Content-Type', 'text/csv; charset=utf-8');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="sales_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Sales Report', "Period: $fromDate to $toDate"]);
        fputcsv($output, []);
        fputcsv($output, ['Product ID', 'Product Name', 'Category', 'Quantity Sold', 'Revenue', 'Orders']);

        foreach ($salesData as $item) {
            fputcsv($output, [
                $item['product_id'],
                $item['name'],
                $item['category'],
                $item['quantity_sold'],
                '$' . number_format((float) $item['revenue'], 2),
                $item['order_count'],
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['Total Items Sold', $totals['totalItems']]);
        fputcsv($output, ['Total Revenue', '$' . number_format($totals['totalRevenue'], 2)]);
        fputcsv($output, ['Total Orders', $totals['totalOrders']]);

        fclose($output);
        return $response;
    }
}