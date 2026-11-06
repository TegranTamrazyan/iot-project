<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\FridgeDashboardModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class FridgeDashboardController extends BaseController
{
    public function __construct(
        Container $container,
        private FridgeDashboardModel $fridgeDashboardModel
    ) {
        parent::__construct($container);
    }

    // Display the fridge dashboard.
    public function view_fridge_dashboard(Request $request, Response $response, array $args = []): Response
    {
        return $this->render(
            $response,
            'fridge_dashboard/fridge_dashboard_view.php',
            [
                'thresholds' => $this->fridgeDashboardModel->getThresholds(),
                'data_url' => BASE_URL . 'fridge-dashboard/data',
                'fan_url' => BASE_URL . 'fridge-dashboard/fan',
                'threshold_url' => BASE_URL . 'fridge-dashboard/threshold',
            ]
        );
    }

    // Return current readings, fan state, and temperature warnings as JSON.
    public function get_dashboard_data(Request $request, Response $response, array $args = []): Response
    {
        try {
            $fridges = [
                'Frig1' => ['temperature' => null, 'humidity' => null, 'threshold' => 5.0, 'recorded_at' => null],
                'Frig2' => ['temperature' => null, 'humidity' => null, 'threshold' => 5.0, 'recorded_at' => null],
            ];

            foreach ($this->fridgeDashboardModel->getLatestReadings() as $row) {
                $fridge = $row['fridge'];
                if (isset($fridges[$fridge])) {
                    $fridges[$fridge]['temperature'] = (float) $row['temperature'];
                    $fridges[$fridge]['humidity'] = (float) $row['humidity'];
                    $fridges[$fridge]['recorded_at'] = $row['recorded_at'];
                }
            }

            foreach ($this->fridgeDashboardModel->getThresholds() as $fridge => $threshold) {
                $fridges[$fridge]['threshold'] = $threshold;
            }

            $alerts = [];
            foreach ($fridges as $fridge => $reading) {
                if ($reading['temperature'] !== null && $reading['temperature'] > $reading['threshold']) {
                    $alerts[] = [
                        'fridge' => $fridge,
                        'temperature' => $reading['temperature'],
                        'threshold_value' => $reading['threshold'],
                        'recorded_at' => $reading['recorded_at'],
                        'message' => $fridge . ' is above its temperature threshold.',
                    ];
                }
            }

            $payload = [
                'fridges' => $fridges,
                'fan_status' => $this->fridgeDashboardModel->getFanStatus(),
                'alerts' => $alerts,
            ];

            $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withHeader('Cache-Control', 'no-store, max-age=0');
        } catch (Throwable $e) {
            $response->getBody()->write((string) json_encode(['error' => 'Unable to load dashboard data.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json; charset=utf-8');
        }
    }

    // Update the fan state.
    public function update_fan(Request $request, Response $response, array $args = []): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $command = strtoupper(trim((string) ($data['command'] ?? '')));

        if (!in_array($command, ['ON', 'OFF'], true)) {
            $response->getBody()->write('Invalid fan command.');
            return $response->withStatus(400);
        }

        try {
            $this->fridgeDashboardModel->setFanStatus($command);
            $_SESSION['success'] = 'Fan state updated to ' . $command . '.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Could not update the fan state.';
        }

        return $this->redirect($request, $response, 'fridge-dashboard.index');
    }

    // Update a fridge's temperature threshold.
    public function update_threshold(Request $request, Response $response, array $args = []): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $fridge = (string) ($data['fridge'] ?? '');
        $rawThreshold = $data['threshold'] ?? null;

        if (!in_array($fridge, ['Frig1', 'Frig2'], true) || !is_scalar($rawThreshold) || !is_numeric((string) $rawThreshold)) {
            $response->getBody()->write('Invalid fridge or temperature threshold.');
            return $response->withStatus(400);
        }

        $threshold = (float) $rawThreshold;
        if (!is_finite($threshold) || $threshold < -20 || $threshold > 30) {
            $response->getBody()->write('Threshold must be between -20 and 30 °C.');
            return $response->withStatus(400);
        }

        try {
            $this->fridgeDashboardModel->updateThreshold($fridge, $threshold);
            $_SESSION['success'] = 'Temperature threshold updated.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Could not update the temperature threshold.';
        }

        return $this->redirect($request, $response, 'fridge-dashboard.index');
    }
}
