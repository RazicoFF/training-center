<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ProfessionController;
use PHPUnit\Framework\TestCase;

final class AdminProfessionVideosTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM profession_videos WHERE profession_id = {$this->professionId}");
    }

    public function testAddVideoRejectsInvalidYoutubeUrl(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/videos", [], [], [
            'csrf_token' => $token,
            'youtube_url' => 'https://example.com/not-a-video',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $count = Database::pdo()->query("SELECT COUNT(*) FROM profession_videos WHERE profession_id = {$this->professionId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testAddThenDeleteVideo(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/videos", [], [], [
            'csrf_token' => $token,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title_uz' => 'Test video',
        ]));

        $pdo = Database::pdo();
        $videoId = (int) $pdo->query("SELECT id FROM profession_videos WHERE profession_id = {$this->professionId}")->fetchColumn();
        $this->assertGreaterThan(0, $videoId);

        $deleteResult = $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/videos/{$videoId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertArrayHasKey('redirect', $deleteResult);
        $count = $pdo->query("SELECT COUNT(*) FROM profession_videos WHERE id = {$videoId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testEditFormRendersVideosList(): void
    {
        Database::pdo()->prepare('INSERT INTO profession_videos (profession_id, youtube_url, title_uz) VALUES (?, ?, ?)')
            ->execute([$this->professionId, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Visible Video Title']);

        $router = new Router();
        (new ProfessionController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/professions/{$this->professionId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Visible Video Title', $html);
    }
}
