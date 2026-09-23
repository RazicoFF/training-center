<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewsDir = dirname(__DIR__, 2) . '/resources/views';

        $renderFile = static function (string $file, array $vars): string {
            extract($vars);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        };

        $content = $renderFile($viewsDir . '/' . $template . '.php', $data);

        echo $renderFile($viewsDir . '/layout.php', array_merge($data, ['content' => $content]));
    }
}
