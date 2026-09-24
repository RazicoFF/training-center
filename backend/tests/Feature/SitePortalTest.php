<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\PortalController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class SitePortalTest extends TestCase
{
    private int $studentId;

    protected function setUp(): void
    {
        $_SESSION = [];
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770050'");
        $this->studentId = (new UserRepository())->create(
            'Portal Student',
            '+998987770050',
            Auth::hashPassword('portalpass1'),
            'student'
        );
    }

    public function testDashboardRedirectsToLoginWhenNotAuthenticated(): void
    {
        $router = new Router();
        (new PortalController())->register($router);

        $result = $router->dispatch(new Request('GET', '/portal', [], [], []));

        $this->assertSame(['redirect' => '/login'], $result);
    }

    public function testLoginThenDashboardShowsStudentName(): void
    {
        $authRouter = new Router();
        (new AuthController())->register($authRouter);
        $token = Csrf::token();

        $loginResult = $authRouter->dispatch(new Request('POST', '/login', [], [], [
            'csrf_token' => $token,
            'phone' => '+998987770050',
            'password' => 'portalpass1',
        ]));

        $this->assertSame(['redirect' => '/portal'], $loginResult);
        $this->assertSame($this->studentId, $_SESSION['site_user_id']);
        $this->assertSame('student', $_SESSION['site_role']);

        $portalRouter = new Router();
        (new PortalController())->register($portalRouter);

        ob_start();
        $dashboardResult = $portalRouter->dispatch(new Request('GET', '/portal', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $dashboardResult);
        $this->assertStringContainsString('Portal Student', $html);
    }

    public function testLoginRejectsNonStudentRole(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770051'");
        (new UserRepository())->create('Portal Teacher', '+998987770051', Auth::hashPassword('teachpass1'), 'teacher');

        $router = new Router();
        (new AuthController())->register($router);
        $token = Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/login', [], [], [
            'csrf_token' => $token,
            'phone' => '+998987770051',
            'password' => 'teachpass1',
        ]));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertArrayNotHasKey('site_user_id', $_SESSION);
    }
}
