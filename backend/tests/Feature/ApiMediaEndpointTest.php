<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\MediaController;
use PHPUnit\Framework\TestCase;

final class ApiMediaEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM media_items WHERE title_uz = 'API Media Test'");
    }

    public function testListsMediaItemsPublicly(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO media_items (type, file_url, title_uz) VALUES ('image', '/uploads/media/x.jpg', 'API Media Test')")
            ->execute();

        $router = new Router();
        (new MediaController())->register($router);

        $result = $router->dispatch(new Request('GET', '/api/v1/media', [], []));

        $this->assertArrayHasKey('media', $result);
        $titles = array_column($result['media'], 'title_uz');
        $this->assertContains('API Media Test', $titles);
    }
}
