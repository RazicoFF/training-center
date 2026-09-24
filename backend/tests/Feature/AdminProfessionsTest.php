<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ProfessionController;
use PHPUnit\Framework\TestCase;

final class AdminProfessionsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $this->professionId = (int) Database::pdo()->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
    }

    public function testIndexRendersProfessionName(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/professions', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Ekskavator', $html);
    }

    public function testUpdateSavesCareerInfo(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}", [], [], [
            'csrf_token' => $token,
            'career_info_uz' => 'Yangi karyera matni',
            'career_info_ru' => 'Новый текст о карьере',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $stmt = Database::pdo()->prepare('SELECT career_info_uz, career_info_ru FROM professions WHERE id = ?');
        $stmt->execute([$this->professionId]);
        $row = $stmt->fetch();

        $this->assertSame('Yangi karyera matni', $row['career_info_uz']);
        $this->assertSame('Новый текст о карьере', $row['career_info_ru']);
    }

    public function testUpdateRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}", [], [], [
            'career_info_uz' => 'Should not be saved',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
