<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TeacherController;
use PHPUnit\Framework\TestCase;

final class AdminTeacherBirthDateTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770250'");
    }

    public function testCreateSavesBirthDate(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', '/admin/teachers', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Birth Date Teacher',
            'phone' => '+998987770250',
            'password' => 'teachpass1',
            'birth_date' => '1985-06-15',
        ]));

        $pdo = Database::pdo();
        $teacherId = (int) $pdo->query("SELECT id FROM users WHERE phone = '+998987770250'")->fetchColumn();
        $birthDate = $pdo->query("SELECT birth_date FROM teacher_profiles WHERE user_id = {$teacherId}")->fetchColumn();

        $this->assertSame('1985-06-15', $birthDate);
    }
}
