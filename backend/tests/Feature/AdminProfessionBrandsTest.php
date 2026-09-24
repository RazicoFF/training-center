<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ProfessionController;
use PHPUnit\Framework\TestCase;

final class AdminProfessionBrandsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM profession_brands WHERE profession_id = {$this->professionId}");
    }

    public function testAddThenDeleteBrand(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/brands", [], [], [
            'csrf_token' => $token,
            'name' => 'Caterpillar',
        ]));

        $pdo = Database::pdo();
        $brandId = (int) $pdo->query("SELECT id FROM profession_brands WHERE profession_id = {$this->professionId} AND name = 'Caterpillar'")->fetchColumn();
        $this->assertGreaterThan(0, $brandId);

        ob_start();
        $editResult = $router->dispatch(new Request('GET', "/admin/professions/{$this->professionId}/edit", [], [], []));
        $html = ob_get_clean();
        $this->assertSame(['rendered' => true], $editResult);
        $this->assertStringContainsString('Caterpillar', $html);

        $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/brands/{$brandId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $count = $pdo->query("SELECT COUNT(*) FROM profession_brands WHERE id = {$brandId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }

    public function testAddBrandRejectsEmptyName(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/brands", [], [], [
            'csrf_token' => $token,
            'name' => '',
        ]));

        $count = Database::pdo()->query("SELECT COUNT(*) FROM profession_brands WHERE profession_id = {$this->professionId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }
}
