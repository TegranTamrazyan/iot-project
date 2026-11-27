<?php

namespace App\Controllers;

use App\Helpers\Core\FlashMessage;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlashDemoController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    // TODO: Implement the methods below

    // index
    public function index(Request $request, Response $response, array $args): Response
    {
        // Return the response object
        return $this->render($response, 'flash/flashIndexView.php', [
            'title' => 'Flash Messages Demo'
        ]);
    }
    // success
    public function success(Request $request, Response $response, array $args): Response
    {
        FlashMessage::success("This is a success message");
        // Return the response object
        return $this->redirect($request, $response, 'flash.demo');
    }
    // error
    public function error(Request $request, Response $response, array $args): Response
    {
        FlashMessage::error("This is a error message");
        // Return the response object
        return $this->redirect($request, $response, 'flash.demo');
    }
    // info
    public function info(Request $request, Response $response, array $args): Response
    {
        FlashMessage::info("This is a info message");
        // Return the response object
        return $this->redirect($request, $response, 'flash.demo');
    }
    // warning
    public function warning(Request $request, Response $response, array $args): Response
    {
        FlashMessage::warning("This is a warning message");
        // Return the response object
        return $this->redirect($request, $response, 'flash.demo');
    }
    // multiple
    public function multiple(Request $request, Response $response, array $args): Response
    {
        FlashMessage::success("This is a success message");
        FlashMessage::warning("This is a warning message");
        // Return the response object
        return $this->redirect($request, $response, 'flash.demo');
    }
}
