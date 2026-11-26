<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Core\FlashMessage;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomerMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $customerId = filter_var($_SESSION['customer_id'] ?? null, FILTER_VALIDATE_INT);
        if ($customerId === false || $customerId < 1) {
            FlashMessage::error('Please log in to access your customer account.');
            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', BASE_URL . 'login');
        }

        return $handler->handle($request);
    }
}
