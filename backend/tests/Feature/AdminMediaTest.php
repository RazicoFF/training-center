<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\MediaController;
use PHPUnit\Framework\TestCase;

final class AdminMediaTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM media_items WHERE youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'");
    }

    public function testAddVideoThenListThenDelete(): void
    {
        $router = new Router();
        (new MediaController())->register($router);
        $token = Csrf::token();

        $createResult = $router->dispatch(new Request('POST', '/admin/media/video', [], [], [
            'csrf_token' => $token,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title_uz' => 'Media Test Video',
        ]));
        $this->assertArrayHasKey('redirect', $createResult);

        $mediaId = (int) Database::pdo()->query("SELECT id FROM media_items WHERE youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'")->fetchColumn();
        $this->assertGreaterThan(0, $mediaId);

        ob_start();
        $indexResult = $router->dispatch(new Request('GET', '/admin/media', [], [], []));
        $html = ob_get_clean();
        $this->assertSame(['rendered' => true], $indexResult);
        $this->assertStringContainsString('Media Test Video', $html);

        $deleteResult = $router->dispatch(new Request('POST', "/admin/media/{$mediaId}/delete", [], [], [
            'csrf_token' => $token,
        ]));
        $this->assertArrayHasKey('redirect', $deleteResult);

        $count = Database::pdo()->query("SELECT COUNT(*) FROM media_items WHERE id = {$mediaId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testAddVideoRejectsInvalidYoutubeUrl(): void
    {
        $router = new Router();
        (new MediaController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/media/video', [], [], [
            'csrf_token' => $token,
            'youtube_url' => 'https://example.com/not-a-video',
        ]));

        $count = Database::pdo()->query("SELECT COUNT(*) FROM media_items WHERE type = 'video'")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testCreateVideoRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new MediaController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/media/video', [], [], [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
