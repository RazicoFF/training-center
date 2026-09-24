<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\StudentController;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminStudentEditTest extends TestCase
{
    private int $studentId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM enrollments WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770060')");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770060'");
        $this->studentId = (new UserRepository())->create('Edit Student', '+998987770060', Auth::hashPassword('origpass1'), 'student');
    }

    public function testEditFormRendersStudentName(): void
    {
        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/students/{$this->studentId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Edit Student', $html);
    }

    public function testUpdateSavesNameAndPhoneWithoutTouchingPasswordWhenBlank(): void
    {
        $originalHash = (new UserRepository())->find($this->studentId)['password_hash'];

        $router = new Router();
        (new StudentController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/students/{$this->studentId}", [], [], [
            'csrf_token' => $token,
            'full_name' => 'Renamed Student',
            'phone' => '+998987770061',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $updated = (new UserRepository())->find($this->studentId);
        $this->assertSame('Renamed Student', $updated['full_name']);
        $this->assertSame('+998987770061', $updated['phone']);
        $this->assertSame($originalHash, $updated['password_hash']);

        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770061'");
    }

    public function testUpdateEnrollmentStatusToCompleted(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Stats Group", "2026-01-01", "2026-01-10")')
            ->execute([$professionId]);
        $groupId = (int) $pdo->lastInsertId();

        $groups = new GroupRepository();
        $groups->enrollStudent($groupId, $this->studentId);
        $enrollmentId = (int) $pdo->query("SELECT id FROM enrollments WHERE user_id = {$this->studentId}")->fetchColumn();

        $router = new Router();
        (new StudentController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/students/{$this->studentId}/enrollments/{$enrollmentId}", [], [], [
            'csrf_token' => $token,
            'status' => 'completed',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $status = $pdo->query("SELECT status FROM enrollments WHERE id = {$enrollmentId}")->fetchColumn();
        $this->assertSame('completed', $status);
    }
}
