<?php

declare(strict_types=1);

namespace App\Core;

final class SiteView
{
    public static function render(string $template, array $data = []): void
    {
        View::render($template, $data, 'site/layout');
    }
}
