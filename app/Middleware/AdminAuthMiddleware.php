<?php

namespace App\Middleware;

use App\Helpers\Core\FlashMessage;
use App\Helpers\Core\SessionManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $userData = SessionManager::get('user_data');

        if (empty($userData['authenticated']) || empty($userData['admin'])) {
            FlashMessage::error('You must login as admin before accessing that page');

            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', APP_BASE_URL . '/login');
        }

        return $handler->handle($request);
    }
}
