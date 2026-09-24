<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\NewsController;
use PHPUnit\Framework\TestCase;

final class NewsEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM news WHERE title_uz = 'Api News'");
    }

    public function testListsNews(): void
    {
        Database::pdo()->prepare('INSERT INTO news (title_uz, title_ru, body_uz) VALUES (?, ?, ?)')
            ->execute(['Api News', 'Новость API', 'Matn']);

        $router = new Router();
        (new NewsController())->register($router);

        $result = $router->dispatch(new Request('GET', '/api/v1/news', [], []));

        $this->assertIsArray($result['news']);
        $titles = array_column($result['news'], 'title_uz');
        $this->assertContains('Api News', $titles);
    }
}
