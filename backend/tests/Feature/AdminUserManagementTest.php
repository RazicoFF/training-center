<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\AdminUserController;
use PHPUnit\Framework\TestCase;

final class AdminUserManagementTest extends TestCase
{
    private int $sessionAdminId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM users WHERE phone IN ('+998987770269', '+998987770270', '+998987770271')");
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Session Admin', '+998987770269', 'x', 'admin')")->execute();
        $this->sessionAdminId = (int) $pdo->lastInsertId();

        $_SESSION = ['admin_user_id' => $this->sessionAdminId, 'admin_role' => 'admin'];
    }

    public function testAdminCanCreateAnotherAdmin(): void
    {
        $router = new Router();
        (new AdminUserController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/admins', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Second Admin',
            'phone' => '+998987770270',
            'password' => 'adminpass1',
        ]));

        $created = Database::pdo()->query("SELECT role FROM users WHERE phone = '+998987770270'")->fetchColumn();
        $this->assertSame('admin', $created);
    }

    public function testAdminCanEditAnotherAdmin(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Old Name', '+998987770271', 'x', 'admin')")->execute();
        $adminId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new AdminUserController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/admins/{$adminId}", [], [], [
            'csrf_token' => $token,
            'full_name' => 'Renamed Admin',
            'phone' => '+998987770271',
        ]));

        $name = $pdo->query("SELECT full_name FROM users WHERE id = {$adminId}")->fetchColumn();
        $this->assertSame('Renamed Admin', $name);
    }

    public function testAdminCanDeleteAnotherAdmin(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Deletable Admin', '+998987770271', 'x', 'admin')")->execute();
        $adminId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new AdminUserController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/admins/{$adminId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $remaining = $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$adminId}")->fetchColumn();
        $this->assertSame(0, (int) $remaining);
    }

    public function testAdminCannotDeleteOwnAccount(): void
    {
        $router = new Router();
        (new AdminUserController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/admins/{$this->sessionAdminId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $stillExists = Database::pdo()->query("SELECT COUNT(*) FROM users WHERE id = {$this->sessionAdminId}")->fetchColumn();
        $this->assertSame(1, (int) $stillExists);
    }

    public function testNonAdminTeacherSessionCannotManageAdmins(): void
    {
        $_SESSION = ['admin_user_id' => $this->sessionAdminId, 'admin_role' => 'teacher'];

        $router = new Router();
        (new AdminUserController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/admins', [], [], []));

        $this->assertSame('/admin/login', $result['redirect'] ?? null);
    }
}
