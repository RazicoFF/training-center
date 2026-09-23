<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\AuthController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminAuthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM users WHERE phone = '+998911111111'");
        (new UserRepository())->create('Admin Test', '+998911111111', Auth::hashPassword('adminpass1'), 'admin');
    }

    public function testLoginFormRendersWithoutCrashing(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/login', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString(\App\Core\Lang::t('login_title'), $html);
        $this->assertStringContainsString('<form', $html);
    }

    public function testLoginWithCorrectCredentialsSetsSessionAndRedirects(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $csrfToken = \App\Core\Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/admin/login', [], [], [
            'phone' => '+998911111111',
            'password' => 'adminpass1',
            'csrf_token' => $csrfToken,
        ]));
        ob_end_clean();

        $this->assertSame(['redirect' => '/admin'], $result);
        $this->assertSame('admin', $_SESSION['admin_role']);
    }

    public function testLoginWithWrongPasswordDoesNotSetSession(): void
    {
        $router = new Router();
        (new AuthController())->register($router);
        $csrfToken = \App\Core\Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/admin/login', [], [], [
            'phone' => '+998911111111',
            'password' => 'wrong',
            'csrf_token' => $csrfToken,
        ]));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertArrayNotHasKey('admin_user_id', $_SESSION);
    }

    public function testLogoutClearsSession(): void
    {
        $_SESSION['admin_user_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $router = new Router();
        (new AuthController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/logout', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
        $this->assertArrayNotHasKey('admin_user_id', $_SESSION);
    }
}
