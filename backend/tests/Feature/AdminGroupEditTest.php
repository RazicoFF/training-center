<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\GroupController;
use PHPUnit\Framework\TestCase;

final class AdminGroupEditTest extends TestCase
{
    private int $groupId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Editable Group", "2026-01-01", "2026-01-10")')
            ->execute([$professionId]);
        $this->groupId = (int) $pdo->lastInsertId();
    }

    public function testEditFormRendersGroupName(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/groups/{$this->groupId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Editable Group', $html);
    }

    public function testUpdateSavesNewNameAndDates(): void
    {
        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/groups/{$this->groupId}/edit", [], [], [
            'csrf_token' => $token,
            'name' => 'Renamed Group',
            'teacher_id' => '',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-20',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $row = Database::pdo()->query("SELECT name, start_date, end_date FROM `groups` WHERE id = {$this->groupId}")->fetch();
        $this->assertSame('Renamed Group', $row['name']);
        $this->assertSame('2026-02-01', $row['start_date']);
        $this->assertSame('2026-02-20', $row['end_date']);
    }
}
