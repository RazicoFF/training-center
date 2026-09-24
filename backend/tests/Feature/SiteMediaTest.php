<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\MediaController;
use PHPUnit\Framework\TestCase;

final class SiteMediaTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        Database::pdo()->exec("DELETE FROM media_items WHERE title_uz = 'Public Gallery Photo'");
        Database::pdo()->prepare("INSERT INTO media_items (type, file_url, title_uz) VALUES ('image', '/uploads/media/sample.jpg', 'Public Gallery Photo')")
            ->execute();
    }

    public function testIndexRendersImageItem(): void
    {
        $router = new Router();
        (new MediaController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/media', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Public Gallery Photo', $html);
        $this->assertStringContainsString('/uploads/media/sample.jpg', $html);
    }
}
