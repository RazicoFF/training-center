<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\AccountController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminAccountTest extends TestCase
{
    private int $adminId;

    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770080'");
        $this->adminId = (new UserRepository())->create(
            'Account Test Admin',
            '+998987770080',
            Auth::hashPassword('originalpass1'),
            'admin'
        );
        $_SESSION = ['admin_user_id' => $this->adminId, 'admin_role' => 'admin'];
    }

    public function testEditFormRenders(): void
    {
        $router = new Router();
        (new AccountController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/account', [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testChangePasswordWithCorrectCurrentPassword(): void
    {
        $router = new Router();
        (new AccountController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/account/password', [], [], [
            'csrf_token' => $token,
            'current_password' => 'originalpass1',
            'new_password' => 'newpassword1',
            'confirm_password' => 'newpassword1',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $user = (new UserRepository())->find($this->adminId);
        $this->assertTrue(Auth::verifyPassword('newpassword1', $user['password_hash']));
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $router = new Router();
        (new AccountController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/account/password', [], [], [
            'csrf_token' => $token,
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword1',
            'confirm_password' => 'newpassword1',
        ]));

        $user = (new UserRepository())->find($this->adminId);
        $this->assertTrue(Auth::verifyPassword('originalpass1', $user['password_hash']));
    }

    public function testChangePasswordRejectsMismatchedConfirmation(): void
    {
        $router = new Router();
        (new AccountController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/account/password', [], [], [
            'csrf_token' => $token,
            'current_password' => 'originalpass1',
            'new_password' => 'newpassword1',
            'confirm_password' => 'different1',
        ]));

        $user = (new UserRepository())->find($this->adminId);
        $this->assertTrue(Auth::verifyPassword('originalpass1', $user['password_hash']));
    }

    public function testChangePasswordRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new AccountController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/account/password', [], [], [
            'current_password' => 'originalpass1',
            'new_password' => 'newpassword1',
            'confirm_password' => 'newpassword1',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
