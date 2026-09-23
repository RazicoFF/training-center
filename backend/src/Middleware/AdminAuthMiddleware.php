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
}
