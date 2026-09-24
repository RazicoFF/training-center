<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\StudentController;
use PHPUnit\Framework\TestCase;

final class AdminStudentsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770010'");
    }

    public function testCreateAddsStudentAndRedirects(): void
    {
        $router = new Router();
        (new StudentController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/students', [], [], [
            'csrf_token' => $token,
            'full_name' => 'New Student',
            'phone' => '+998987770010',
            'password' => 'studpass1',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $role = Database::pdo()->query("SELECT role FROM users WHERE phone = '+998987770010'")->fetchColumn();
        $this->assertSame('student', $role);
    }

    public function testCreateRedirectsTeacherRoleAwayFromAdminOnlyPage(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'teacher'];

        $router = new Router();
        (new StudentController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/students', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Blocked Student',
            'phone' => '+998987770010',
            'password' => 'studpass1',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
        $exists = Database::pdo()->query("SELECT COUNT(*) FROM users WHERE phone = '+998987770010'")->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    public function testListRendersStudent(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('List Student', '+998987770010', 'x', 'student')")->execute();

        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/students', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('List Student', $html);
    }
}
