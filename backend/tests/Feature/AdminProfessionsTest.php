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
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM professions WHERE name_uz IN ('Smoke Profession', 'Editable Fixture Profession')");
        $pdo->prepare(
            'INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute(['Editable Fixture Profession', 'Тестовая профессия для правки', 'Tavsif', 'Описание', 10, 500000]);
        $this->professionId = (int) $pdo->lastInsertId();
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

    public function testUpdateSavesAllFieldsIncludingPriceAndCareerInfo(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}", [], [], [
            'csrf_token' => $token,
            'name_uz' => 'Yangilangan kasb',
            'name_ru' => 'Обновлённая профессия',
            'description_uz' => 'Tavsif',
            'description_ru' => 'Описание',
            'duration_days' => '25',
            'price' => '2000000',
            'career_info_uz' => 'Yangi karyera matni',
            'career_info_ru' => 'Новый текст о карьере',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $stmt = Database::pdo()->prepare('SELECT * FROM professions WHERE id = ?');
        $stmt->execute([$this->professionId]);
        $row = $stmt->fetch();

        $this->assertSame('Yangilangan kasb', $row['name_uz']);
        $this->assertSame(25, (int) $row['duration_days']);
        $this->assertSame(2000000, (int) $row['price']);
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

    public function testCreateAddsNewProfession(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/professions', [], [], [
            'csrf_token' => $token,
            'name_uz' => 'Smoke Profession',
            'name_ru' => 'Тестовая профессия',
            'description_uz' => 'Tavsif',
            'description_ru' => 'Описание',
            'duration_days' => '15',
            'price' => '900000',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $newId = (int) Database::pdo()->query("SELECT id FROM professions WHERE name_uz = 'Smoke Profession'")->fetchColumn();
        $this->assertGreaterThan(0, $newId);

        // Redirects to the edit page (not the index) so the admin can add videos/PDF right away.
        $this->assertSame("/admin/professions/{$newId}/edit", $result['redirect']);
    }

    public function testCreateRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/professions', [], [], [
            'name_uz' => 'Smoke Profession',
            'name_ru' => 'Тестовая профессия',
            'duration_days' => '15',
            'price' => '900000',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
        $count = Database::pdo()->query("SELECT COUNT(*) FROM professions WHERE name_uz = 'Smoke Profession'")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }
}
