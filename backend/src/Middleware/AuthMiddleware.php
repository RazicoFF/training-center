<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class AuthMiddleware
{
    public static function authenticate(Request $request): ?array
    {
        $header = $request->header('AUTHORIZATION') ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        $claims = Auth::verifyToken($token);

        if ($claims !== null) {
            $request->setUser($claims);
        }

        return $claims;
    }
}
