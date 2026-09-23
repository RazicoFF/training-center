<?php

declare(strict_types=1);

namespace App\Middleware;

final class AdminAuthMiddleware
{
    /**
     * @return array{user_id:int, role:string}|null
     */
    public static function authenticate(): ?array
    {
        if (!isset($_SESSION['admin_user_id'], $_SESSION['admin_role'])) {
            return null;
        }

        return [
            'user_id' => (int) $_SESSION['admin_user_id'],
            'role' => (string) $_SESSION['admin_role'],
        ];
    }

    /**
     * Same as authenticate(), but additionally requires the session's role to be 'admin'.
     * Use this for pages the spec restricts to role=admin (most of the admin panel);
     * a 'teacher' session gets null (redirected to login) just like an anonymous visitor.
     *
     * @return array{user_id:int, role:string}|null
     */
    public static function requireAdmin(): ?array
    {
        $claims = self::authenticate();

        if ($claims === null || $claims['role'] !== 'admin') {
            return null;
        }

        return $claims;
    }
}
