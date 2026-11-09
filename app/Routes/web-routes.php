<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\UserController;
use App\Controllers\FridgeDashboardController;
use App\Controllers\CheckoutController;
use App\Helpers\Core\DateTimeHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

    // Preserve the original fridge dashboard and its API routes.
    $app->get('/fridge-dashboard',[FridgeDashboardController::class,'view_fridge_dashboard'])
        ->setName('fridge-dashboard.index');
    $app->get('/fridge-dashboard/data',[FridgeDashboardController::class,'get_dashboard_data'])
        ->setName('fridge-dashboard.data');
    $app->post('/fridge-dashboard/fan',[FridgeDashboardController::class,'update_fan'])
        ->setName('fridge-dashboard.fan');
    $app->post('/fridge-dashboard/threshold',[FridgeDashboardController::class,'update_threshold'])
        ->setName('fridge-dashboard.threshold');
};
