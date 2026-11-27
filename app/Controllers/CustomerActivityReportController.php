<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CustomerActivityReportController extends BaseController
{
    public function __construct(Container $container, private CheckoutModel $model)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $hasDateFilters = array_key_exists('from', $query) || array_key_exists('to', $query);
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $fromValue = $query['from'] ?? null;
        $toValue = $query['to'] ?? null;
        $fromDate = $hasDateFilters
            ? (is_string($fromValue) ? trim($fromValue) : '')
            : $today;
        $toDate = $hasDateFilters
            ? (is_string($toValue) ? trim($toValue) : '')
            : $today;
        $reportError = '';
        $activity = [
            'totalCustomers' => 0,
            'newCustomers' => 0,
            'returningCustomers' => 0,
            'dailyActivity' => [],
            'busiestDay' => null,
            'leastBusyDay' => null,
        ];

        if ($fromDate === '' || $toDate === '') {
            $reportError = 'Select both a start date and an end date.';
        } elseif (!$this->isValidDate($fromDate)) {
            $reportError = 'Start date must be a valid date.';
        } elseif (!$this->isValidDate($toDate)) {
            $reportError = 'End date must be a valid date.';
        } elseif ($fromDate > $toDate) {
            $reportError = 'Start date must be on or before end date.';
        } else {
            $activity = $this->model->customerActivity($fromDate, $toDate);
        }

        return $this->render($response, 'admin/customer_activity_report.php', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'reportError' => $reportError,
            'activity' => $activity,
        ]);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    }
}
