<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\PasswordResetController as AdminPasswordResetController;
use App\Controllers\Site\PasswordResetController as SitePasswordResetController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM password_reset_requests WHERE phone IN ('+998987770400', '+998987770401', '+998987779999')");
        $pdo->exec("DELETE FROM users WHERE phone IN ('+998987770400', '+998987770401')");
    }

    public function testSubmittingWithAMatchingStudentPhoneCreatesAPendingRequest(): void
    {
        $studentId = (new UserRepository())->create('Reset Student', '+998987770400', Auth::hashPassword('oldpass1'), 'student');

        $router = new Router();
        (new SitePasswordResetController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/forgot-password', [], [], [
            'csrf_token' => $token,
            'phone' => '+998987770400',
        ]));

        $this->assertSame(['rendered' => true], $result);

        $pdo = Database::pdo();
        $row = $pdo->query("SELECT user_id, status FROM password_reset_requests WHERE phone = '+998987770400'")->fetch();
        $this->assertNotFalse($row);
        $this->assertSame($studentId, (int) $row['user_id']);
        $this->assertSame('pending', $row['status']);
    }

    public function testSubmittingWithAnUnknownPhoneCreatesNoRequestButStillRendersConfirmation(): void
    {
        $router = new Router();
        (new SitePasswordResetController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/forgot-password', [], [], [
            'csrf_token' => $token,
            'phone' => '+998987779999',
        ]));

        $this->assertSame(['rendered' => true], $result);

        $count = Database::pdo()->query("SELECT COUNT(*) FROM password_reset_requests WHERE phone = '+998987779999'")->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testSubmittingWithAnAdminPhoneCreatesNoRequest(): void
    {
        (new UserRepository())->create('Reset Admin', '+998987770401', Auth::hashPassword('adminpass1'), 'admin');

        $router = new Router();
        (new SitePasswordResetController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/forgot-password', [], [], [
            'csrf_token' => $token,
            'phone' => '+998987770401',
        ]));

        $count = Database::pdo()->query("SELECT COUNT(*) FROM password_reset_requests WHERE phone = '+998987770401'")->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testAdminResolvingARequestSetsTheNewPasswordAndMarksResolved(): void
    {
        $studentId = (new UserRepository())->create('Reset Student', '+998987770400', Auth::hashPassword('oldpass1'), 'student');
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO password_reset_requests (phone, user_id) VALUES (?, ?)')->execute(['+998987770400', $studentId]);
        $requestId = (int) $pdo->lastInsertId();

        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $router = new Router();
        (new AdminPasswordResetController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/password-resets/{$requestId}/resolve", [], [], [
            'csrf_token' => $token,
            'password' => 'brandnewpass1',
        ]));

        $this->assertSame('/admin/password-resets', $result['redirect']);

        $status = $pdo->query("SELECT status FROM password_reset_requests WHERE id = {$requestId}")->fetchColumn();
        $this->assertSame('resolved', $status);

        $user = (new UserRepository())->find($studentId);
        $this->assertTrue(Auth::verifyPassword('brandnewpass1', $user['password_hash']));
    }

    public function testTeacherSessionCannotAccessPasswordResets(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'teacher'];
        $router = new Router();
        (new AdminPasswordResetController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/password-resets', [], [], []));

        $this->assertSame('/admin/login', $result['redirect'] ?? null);
    }
}
