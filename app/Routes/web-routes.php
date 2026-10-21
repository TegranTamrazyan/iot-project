<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\AddingUserController;
use App\Controllers\FridgeDashboardController;
use App\Helpers\Core\DateTimeHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return static function (Slim\App $app): void {

    // ROUTE: GET /
    $app->get('/', [AboutController::class, 'handleAboutWebService']);

    // ROUTE: GET /ping
    $app->get('/ping', function (Request $request, Response $response, $args) {
        $payload = [
            'greetings' => 'Reporting! Hello there!',
            'now' => DateTimeHelper::now(DateTimeHelper::Y_M_D_H_M),
        ];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
        return $response;
    });

    // Example route to test error handling.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, 'Something went wrong');
    });

    // ROUTE: GET /phpinfo
    $app->get('/phpinfo', function (Request $request, Response $response, $args) {
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        $response->getBody()->write($info);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Add customer routes.
    $app->get('/adding', [AddingUserController::class, 'view_adding_user'])->setName('adding.index');
    $app->post('/adding', [AddingUserController::class, 'store_user'])->setName('adding.store');

    // Fridge dashboard routes. The existing public/index.php remains the only front controller.
    $app->get('/fridge-dashboard', [FridgeDashboardController::class, 'view_fridge_dashboard'])
        ->setName('fridge-dashboard.index');
    $app->get('/fridge-dashboard/data', [FridgeDashboardController::class, 'get_dashboard_data'])
        ->setName('fridge-dashboard.data');
    $app->post('/fridge-dashboard/fan', [FridgeDashboardController::class, 'update_fan'])
        ->setName('fridge-dashboard.fan');
    $app->post('/fridge-dashboard/threshold', [FridgeDashboardController::class, 'update_threshold'])
        ->setName('fridge-dashboard.threshold');
};
