<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\GroupController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminGroupsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM enrollments');
        $pdo->exec('DELETE FROM schedule');
        $pdo->exec('DELETE FROM `groups`');
        $pdo->exec("DELETE FROM users WHERE phone = '+998944444444'");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        (new UserRepository())->create('Group Student', '+998944444444', Auth::hashPassword('x'), 'student');
    }

    public function testCreateFormRouteIsNotSwallowedByIdRoute(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/groups/create', [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testCreateGeneratesScheduleForWholeCourse(): void
    {
        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/groups', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'teacher_id' => '',
            'name' => 'Test Group A',
            'start_date' => '2026-02-02',
            'end_date' => '2026-02-15',
            'weekdays' => ['1', '3'],
            'start_time' => '09:00',
            'end_time' => '11:00',
            'room' => '201',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $groupId = (int) Database::pdo()->query("SELECT id FROM `groups` WHERE name = 'Test Group A'")->fetchColumn();
        $scheduleCount = (int) Database::pdo()->query("SELECT COUNT(*) FROM schedule WHERE group_id = {$groupId}")->fetchColumn();
        $this->assertSame(4, $scheduleCount);
    }

    public function testEnrollAddsStudentToGroup(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Enroll Group", "2026-01-01", "2026-01-01")')
            ->execute([$this->professionId]);
        $groupId = (int) $pdo->lastInsertId();
        $studentId = (int) $pdo->query("SELECT id FROM users WHERE phone = '+998944444444'")->fetchColumn();

        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/groups/{$groupId}/enroll", [], [], [
            'csrf_token' => $token,
            'user_id' => (string) $studentId,
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $count = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE group_id = {$groupId} AND user_id = {$studentId}")->fetchColumn();
        $this->assertSame(1, $count);
    }
}
