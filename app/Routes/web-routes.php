<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\UserController;
use App\Controllers\FridgeDashboardController;
use App\Controllers\CheckoutController;
use App\Controllers\CustomerActivityReportController;
use App\Controllers\InventoryReportController;
use App\Controllers\ProductController;
use App\Controllers\SalesReportController;
use App\Helpers\Core\DateTimeHelper;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CustomerMiddleware;
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
    $customerMiddleware = new CustomerMiddleware($app->getResponseFactory());
    $app->group('/account', function($group) {
        $group->get('',[UserController::class,'account'])->setName('account.index');
        $group->get('/report',[UserController::class,'purchaseReport'])->setName('account.report');
        $group->post('/resend-receipt',[UserController::class,'resendReceipt']);
    })->add($customerMiddleware);
    $app->post('/logout',[UserController::class,'logout']);

    // SELF CHECKOUT: cart, RFID batch, membership selection and simulated payment.
    // Product CRUD and notification systems are intentionally omitted.
    $app->get('/checkout',[CheckoutController::class,'index']);
    $app->post('/checkout/add',[CheckoutController::class,'add']);
    $app->post('/checkout/remove',[CheckoutController::class,'remove']);
    $app->post('/checkout/clear',[CheckoutController::class,'clear']);
    $app->post('/checkout/restore-tag',[CheckoutController::class,'restoreTag']);
    $app->get('/checkout/confirm',[CheckoutController::class,'confirm']);
    $app->post('/checkout/pay',[CheckoutController::class,'pay']);
    $app->get('/checkout/done',[CheckoutController::class,'done']);
    $app->post('/checkout/email-receipt',[CheckoutController::class,'emailReceipt']);
    $app->post('/checkout/api/scan',[CheckoutController::class,'scanApi']);
    $app->post('/rfid/scan', [ProductController::class, 'receiveRfidScan']);
    $app->post('/checkout/scanner/activate', [ProductController::class, 'activateCheckoutScanner']);

    $adminMiddleware = new AuthMiddleware($app->getResponseFactory());

    // Preserve the original fridge dashboard and its API routes.
    $app->group('/fridge-dashboard', function($group) {
        $group->get('', [FridgeDashboardController::class, 'view_fridge_dashboard'])->setName('fridge-dashboard.index');

        $group->get('/data', [FridgeDashboardController::class, 'get_dashboard_data']);

        $group->post('/fan', [FridgeDashboardController::class, 'update_fan']);

        $group->post('/threshold', [FridgeDashboardController::class, 'update_threshold']);
    })->add($adminMiddleware);

    // Product management (admin only)
    $app->group('/product', function($group) {
        $group->get('', [ProductController::class, 'index']);
        $group->get('/customer-activity', [CustomerActivityReportController::class, 'index'])
            ->setName('product.customer-activity');
        $group->post('/create', [ProductController::class, 'create']);
        $group->get('/edit/{id}', [ProductController::class, 'edit']);
        $group->post('/edit/{id}', [ProductController::class, 'update']);
        $group->post('/delete/{id}', [ProductController::class, 'delete']);
        $group->post('/delete', [ProductController::class, 'deleteMultiple']);
        $group->post('/product/api/scan/{id}', [ProductController::class, 'scanEpcs']);
        $group->post('/edit/{id}/scanner/activate', [ProductController::class, 'activateProductScanner']);
        $group->get('/edit/{id}/scanner/count', [ProductController::class, 'pendingRfidCount']);
        $group->post('/edit/{id}/epcs/clear', [ProductController::class, 'clearEpcs']);
    })->add($adminMiddleware);

    // Reports (admin only)
    $app->group('/reports', function($group) {
        $group->get('/inventory', [InventoryReportController::class, 'index'])
            ->setName('reports.inventory');
        $group->get('/sales', [SalesReportController::class, 'index'])
            ->setName('reports.sales');
    })->add($adminMiddleware);
};
