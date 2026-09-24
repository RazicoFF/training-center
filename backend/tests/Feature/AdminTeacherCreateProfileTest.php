<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TeacherController;
use PHPUnit\Framework\TestCase;

final class AdminTeacherCreateProfileTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM teacher_profiles WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770070')");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770070'");
    }

    public function testCreateSavesOptionalProfileFieldsImmediately(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/teachers', [], [], [
            'csrf_token' => $token,
            'full_name' => 'New Teacher With Profile',
            'phone' => '+998987770070',
            'password' => 'teachpass1',
            'age' => '35',
            'experience_years' => '9',
            'skills_uz' => 'Avtosamosval boshqarish',
            'telegram' => '@newteacher',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $pdo = Database::pdo();
        $teacherId = (int) $pdo->query("SELECT id FROM users WHERE phone = '+998987770070'")->fetchColumn();
        $this->assertGreaterThan(0, $teacherId);

        $profile = $pdo->query("SELECT * FROM teacher_profiles WHERE user_id = {$teacherId}")->fetch();
        $this->assertNotFalse($profile);
        $this->assertSame(35, (int) $profile['age']);
        $this->assertSame(9, (int) $profile['experience_years']);
        $this->assertSame('Avtosamosval boshqarish', $profile['skills_uz']);
        $this->assertSame('@newteacher', $profile['telegram']);
    }

    public function testCreateWithoutOptionalFieldsStillWorks(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/teachers', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Minimal Teacher',
            'phone' => '+998987770070',
            'password' => 'teachpass1',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $pdo = Database::pdo();
        $teacherId = (int) $pdo->query("SELECT id FROM users WHERE phone = '+998987770070'")->fetchColumn();
        $profile = $pdo->query("SELECT * FROM teacher_profiles WHERE user_id = {$teacherId}")->fetch();
        $this->assertNotFalse($profile);
        $this->assertNull($profile['age']);
    }
}
