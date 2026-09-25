<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

final class Env
{
    /**
     * safeLoad (not load) so a production deployment that sets real environment
     * variables (Railway, etc.) instead of shipping a .env file doesn't crash here.
     */
    public static function load(string $rootPath, string $file = '.env'): void
    {
        Dotenv::createImmutable($rootPath, $file)->safeLoad();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : (string) $value;
    }
}
