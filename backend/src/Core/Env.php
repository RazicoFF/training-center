<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

final class Env
{
    public static function load(string $rootPath, string $file = '.env'): void
    {
        Dotenv::createImmutable($rootPath, $file)->load();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : (string) $value;
    }
}
