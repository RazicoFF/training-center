<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\GroupController;
use PHPUnit\Framework\TestCase;

final class AdminGroupBrandTest extends TestCase
{
    private int $professionId;
    private int $brandId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM `groups` WHERE name = 'Brand Group Test'");
        $pdo->exec("DELETE FROM profession_brands WHERE name = 'Brand Group Test Brand'");
        $pdo->prepare('INSERT INTO profession_brands (profession_id, name) VALUES (?, ?)')->execute([$this->professionId, 'Brand Group Test Brand']);
        $this->brandId = (int) $pdo->lastInsertId();
    }

    public function testCreatingAGroupSavesTheSelectedBrand(): void
    {
        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/groups', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'brand_id' => (string) $this->brandId,
            'name' => 'Brand Group Test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $pdo = Database::pdo();
        $savedBrandId = $pdo->query("SELECT brand_id FROM `groups` WHERE name = 'Brand Group Test'")->fetchColumn();
        $this->assertSame($this->brandId, (int) $savedBrandId);
    }
}
