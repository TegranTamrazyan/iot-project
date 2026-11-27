<?php

namespace App\Middleware;

use App\Helpers\Core\FlashMessage;
use App\Helpers\Core\SessionManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (empty($_SESSION['is_logged_in'])) {
            FlashMessage::error('You must be logged in before accessing that page');

            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', APP_BASE_URL . '/login');
        }

        if (empty($_SESSION['is_admin'])) {
            FlashMessage::error('You must be an admin before accessing that page');

            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', APP_BASE_URL . '/checkout');
        }

        return $handler->handle($request);
    }
}
