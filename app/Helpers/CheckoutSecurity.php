<?php

declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ServerRequestInterface as Request;

/** Small checkout-only CSRF helper. No JavaScript. */
final class CheckoutSecurity
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['checkout_csrf'])) {
            $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['checkout_csrf'];
    }

    public static function validCsrf(Request $request): bool
    {
        $body = (array) ($request->getParsedBody() ?? []);
        $token = (string) ($body['csrf'] ?? '');
        return $token !== '' && hash_equals(self::csrfToken(), $token);
    }

}
