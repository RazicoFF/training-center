<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\NewsController;
use PHPUnit\Framework\TestCase;

final class AdminNewsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM news WHERE title_uz = 'Smoke News'");
    }

    public function testCreateAddsNewsAndListsIt(): void
    {
        $router = new Router();
        (new NewsController())->register($router);
        $token = Csrf::token();

        $createResult = $router->dispatch(new Request('POST', '/admin/news', [], [], [
            'csrf_token' => $token,
            'title_uz' => 'Smoke News',
            'title_ru' => 'Тестовая новость',
            'body_uz' => 'Matn',
            'body_ru' => 'Текст',
        ]));

        $this->assertArrayHasKey('redirect', $createResult);

        $newsId = (int) Database::pdo()->query("SELECT id FROM news WHERE title_uz = 'Smoke News'")->fetchColumn();
        $this->assertGreaterThan(0, $newsId);

        ob_start();
        $indexResult = $router->dispatch(new Request('GET', '/admin/news', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $indexResult);
        $this->assertStringContainsString('Smoke News', $html);
    }

    public function testUpdateAndDelete(): void
    {
        $router = new Router();
        (new NewsController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/news', [], [], [
            'csrf_token' => $token,
            'title_uz' => 'Smoke News',
            'title_ru' => 'Тестовая новость',
        ]));

        $newsId = (int) Database::pdo()->query("SELECT id FROM news WHERE title_uz = 'Smoke News'")->fetchColumn();

        $updateResult = $router->dispatch(new Request('POST', "/admin/news/{$newsId}", [], [], [
            'csrf_token' => $token,
            'title_uz' => 'Updated News',
            'title_ru' => 'Обновлённая новость',
        ]));
        $this->assertArrayHasKey('redirect', $updateResult);

        $title = Database::pdo()->query("SELECT title_uz FROM news WHERE id = {$newsId}")->fetchColumn();
        $this->assertSame('Updated News', $title);

        $deleteResult = $router->dispatch(new Request('POST', "/admin/news/{$newsId}/delete", [], [], [
            'csrf_token' => $token,
        ]));
        $this->assertArrayHasKey('redirect', $deleteResult);

        $count = Database::pdo()->query("SELECT COUNT(*) FROM news WHERE id = {$newsId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testCreateRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new NewsController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/news', [], [], [
            'title_uz' => 'Smoke News',
            'title_ru' => 'Тестовая новость',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
