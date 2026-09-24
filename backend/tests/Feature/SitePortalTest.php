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

    public function testClosedTestCannotBeOpenedOrSubmitted(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Portal Test Group", "2026-01-01", "2026-12-31")')
            ->execute([$professionId]);
        $groupId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO enrollments (user_id, group_id, status) VALUES (?, ?, "active")')
            ->execute([$this->studentId, $groupId]);

        $closesAt = (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score, closes_at) VALUES (?, "Closed Test", "Закрытый тест", 70, ?)')
            ->execute([$professionId, $closesAt]);
        $testId = (int) $pdo->lastInsertId();

        $_SESSION = ['site_user_id' => $this->studentId, 'site_role' => 'student'];

        $router = new Router();
        (new PortalController())->register($router);

        ob_start();
        $showResult = $router->dispatch(new Request('GET', "/portal/tests/{$testId}", [], [], []));
        ob_end_clean();
        $this->assertSame(['redirect' => '/portal/tests', 'flash' => 'Test hozircha yopiq'], $showResult);

        $token = Csrf::token();
        $submitResult = $router->dispatch(new Request('POST', "/portal/tests/{$testId}/submit", [], [], [
            'csrf_token' => $token,
        ]));
        $this->assertSame(['redirect' => '/portal/tests', 'flash' => 'Test hozircha yopiq'], $submitResult);

        $attemptCount = $pdo->query("SELECT COUNT(*) FROM test_attempts WHERE test_id = {$testId}")->fetchColumn();
        $this->assertSame('0', (string) $attemptCount);
    }
}
