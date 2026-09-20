<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Models\TwoFactorAuthModel;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteContext;
use App\Helpers\Core\SessionManager;
use App\Domain\Models\TrustedDeviceModel;

class TwoFactorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TwoFactorAuthModel $twoFactorModel,
        private TrustedDeviceModel $trustedDeviceModel,
        private ResponseFactoryInterface $responseFactory
    ) {}

    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // TODO: Retrieve the user's authentication status from the session.
        //       If not authenticated, pass the request to the next handler.
        $userData = SessionManager::get('user_data');

        if (empty($userData) || empty($userData['id'])) {
            return $handler->handle($request);
        }

        $userId = (int) $userData['id'];

        //       If authenticated, check whether the user has 2FA enabled
        //       and whether it has been verified this session.

        $twoFactorEnabled = $this->twoFactorModel->isEnabled($userId);
        $twoFactorVerified = SessionManager::get('2fa_verified') === true;

        if (!$twoFactorEnabled || $twoFactorVerified) {
            return $handler->handle($request);
        }

        $cookies = $request->getCookieParams();
        $deviceToken = $cookies['trusted_device'] ?? null;

        if ($deviceToken && $this->trustedDeviceModel->isValid($deviceToken, $userId)) {
            SessionManager::set('2fa_verified', true);
            $this->trustedDeviceModel->updateLastUsed($deviceToken);

            return $handler->handle($request);
        }

        if ($deviceToken) {
            setcookie('trusted_device', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        //       If 2FA is required but not verified, redirect to the
        //       verification page.
        //
        //       Use the following to generate the verification URL and
        //       create a redirect response:
        //       $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        //       $verifyUrl = $routeParser->urlFor('2fa.verify');
        //       $response = $this->responseFactory->createResponse(302);
        //       return $response->withHeader('Location', $verifyUrl);

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $verifyUrl = $routeParser->urlFor('2fa.verify');

        $response = $this->responseFactory->createResponse(302);
        return $response->withHeader('Location', $verifyUrl);
    }
}
