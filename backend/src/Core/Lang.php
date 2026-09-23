<?php

declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static ?array $strings = null;
    private static ?string $loadedLocale = null;

    public static function set(string $locale): void
    {
        if (!in_array($locale, ['uz', 'ru'], true)) {
            return;
        }

        $_SESSION['admin_lang'] = $locale;
        self::$strings = null;
    }

    public static function current(): string
    {
        return $_SESSION['admin_lang'] ?? 'uz';
    }

    public static function t(string $key): string
    {
        $locale = self::current();

        if (self::$strings === null || self::$loadedLocale !== $locale) {
            self::$strings = require dirname(__DIR__, 2) . '/resources/lang/' . $locale . '.php';
            self::$loadedLocale = $locale;
        }

        return self::$strings[$key] ?? $key;
    }
}
