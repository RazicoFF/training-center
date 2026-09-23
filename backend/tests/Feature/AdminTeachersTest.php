<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TeacherController;
use PHPUnit\Framework\TestCase;

final class AdminTeachersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998955555555'");
    }

    public function testCreateAddsTeacherAndRedirects(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/teachers', [], [], [
            'csrf_token' => $token,
            'full_name' => 'New Teacher',
            'phone' => '+998955555555',
            'password' => 'teachpass1',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $role = Database::pdo()->query("SELECT role FROM users WHERE phone = '+998955555555'")->fetchColumn();
        $this->assertSame('teacher', $role);
    }

    public function testListRendersTeacher(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('List Teacher', '+998955555555', 'x', 'teacher')")->execute();

        $router = new Router();
        (new TeacherController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/teachers', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('List Teacher', $html);
    }
}
