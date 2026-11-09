<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\UserController;
use App\Controllers\FridgeDashboardController;
use App\Controllers\CheckoutController;
use App\Helpers\Core\DateTimeHelper;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

return static function (Slim\App $app): void {
    // No /iot-project-main prefix: run `php -S localhost:8080 -t public`.
    $app->get('/',function(Request $request,Response $response) {
        return $response->withStatus(302)->withHeader('Location',BASE_URL.'checkout');
    });
    $app->get('/about',[AboutController::class,'handleAboutWebService']);
    $app->get('/ping',function(Request $req,Response $res) {
        $res->getBody()->write(json_encode([
            'greetings'=>'Reporting! Hello there!',
            'now'=>DateTimeHelper::now(DateTimeHelper::Y_M_D_H_M),
        ]));
        return $res->withHeader('Content-Type','application/json');
    });

    // CUSTOMER: register, password login, membership card, points and history.
    $app->get('/adding',[UserController::class,'view_adding_user'])->setName('adding.index');
    $app->post('/adding',[UserController::class,'store_user'])->setName('adding.store');
    $app->get('/login',[UserController::class,'view_login_user'])->setName('login.view');
    $app->post('/login',[UserController::class,'login'])->setName('login.verify');
    $app->get('/account',[UserController::class,'account'])->setName('account.index');
    $app->post('/logout',[UserController::class,'logout']);

    // SELF CHECKOUT: cart, RFID batch, membership selection and simulated payment.
    // No product CRUD, receipt views, email or notification system.
    $app->get('/checkout',[CheckoutController::class,'index']);
    $app->post('/checkout/add',[CheckoutController::class,'add']);
    $app->post('/checkout/remove',[CheckoutController::class,'remove']);
    $app->post('/checkout/clear',[CheckoutController::class,'clear']);
    $app->post('/checkout/restore-tag',[CheckoutController::class,'restoreTag']);
    $app->get('/checkout/confirm',[CheckoutController::class,'confirm']);
    $app->post('/checkout/pay',[CheckoutController::class,'pay']);
    $app->get('/checkout/done',[CheckoutController::class,'done']);
    $app->post('/checkout/api/scan',[CheckoutController::class,'scanApi']);

    $adminMiddleware = new AuthMiddleware($app->getResponseFactory());

    // Preserve the original fridge dashboard and its API routes.
    $app->group('/fridge-dashboard', function($group) {
        $group->get('', [FridgeDashboardController::class, 'view_fridge_dashboard']);

        $group->get('/data', [FridgeDashboardController::class, 'get_dashboard_data']);

        $group->post('/fan', [FridgeDashboardController::class, 'update_fan']);

        $group->post('/threshold', [FridgeDashboardController::class, 'update_threshold']);
    })->add($adminMiddleware);
};
