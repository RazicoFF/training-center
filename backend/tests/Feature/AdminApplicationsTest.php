<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ApplicationController;
use PHPUnit\Framework\TestCase;

final class AdminApplicationsTest extends TestCase
{
    private int $professionId;
    private int $applicationId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998933333333'");
        $pdo->exec("DELETE FROM users WHERE phone = '+998933333333'");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, status) VALUES (?, ?, ?, "pending")')
            ->execute(['App Test', '+998933333333', $this->professionId]);
        $this->applicationId = (int) $pdo->lastInsertId();
    }

    public function testListRendersPendingApplication(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/applications', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('App Test', $html);
    }

    public function testApproveCreatesUserAndRedirects(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/applications/{$this->applicationId}/approve", [], [], [
            'password' => 'temp12345',
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/applications', $result['redirect']);
        $status = Database::pdo()->query("SELECT status FROM applications WHERE id = {$this->applicationId}")->fetchColumn();
        $this->assertSame('approved', $status);
    }

    public function testApproveWithPhoneAlreadyRegisteredFlashRedirectsInsteadOf500(): void
    {
        $pdo = Database::pdo();
        // The application's phone number already belongs to an existing user (e.g. a
        // duplicate application approved twice under different application rows), which
        // trips the UNIQUE constraint on users.phone during approval.
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Existing User', ?, 'x', 'student')")
            ->execute(['+998933333333']);

        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/applications/{$this->applicationId}/approve", [], [], [
            'password' => 'temp12345',
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/applications', $result['redirect']);
        $this->assertArrayHasKey('flash', $result);

        $status = Database::pdo()->query("SELECT status FROM applications WHERE id = {$this->applicationId}")->fetchColumn();
        $this->assertSame('pending', $status);
    }

    public function testRejectUpdatesStatusAndRedirects(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/applications/{$this->applicationId}/reject", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/applications', $result['redirect']);
        $status = Database::pdo()->query("SELECT status FROM applications WHERE id = {$this->applicationId}")->fetchColumn();
        $this->assertSame('rejected', $status);
    }
}
