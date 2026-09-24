<?php

declare(strict_types=1);

namespace App\Middleware;

final class SiteAuthMiddleware
{
    /**
     * @return array{user_id:int}|null
     */
    public static function authenticate(): ?array
    {
        if (!isset($_SESSION['site_user_id'], $_SESSION['site_role']) || $_SESSION['site_role'] !== 'student') {
            return null;
        }

        return ['user_id' => (int) $_SESSION['site_user_id']];
    }
}
