<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TeacherController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminTeacherProfileEditTest extends TestCase
{
    private int $teacherId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM teacher_profiles WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770020')");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770020'");
        $this->teacherId = (new UserRepository())->create('Edit Teacher', '+998987770020', 'x', 'teacher');
    }

    public function testEditFormRendersTeacherName(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/teachers/{$this->teacherId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Edit Teacher', $html);
    }

    public function testUpdateSavesOptionalFieldsWithoutRequiringAllOfThem(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/teachers/{$this->teacherId}", [], [], [
            'csrf_token' => $token,
            'age' => '45',
            'skills_uz' => 'Ekskavator boshqarish',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $stmt = Database::pdo()->prepare('SELECT * FROM teacher_profiles WHERE user_id = ?');
        $stmt->execute([$this->teacherId]);
        $profile = $stmt->fetch();

        $this->assertNotFalse($profile);
        $this->assertSame(45, (int) $profile['age']);
        $this->assertSame('Ekskavator boshqarish', $profile['skills_uz']);
        $this->assertNull($profile['email']);
        $this->assertNull($profile['telegram']);
    }

    public function testUpdateRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);

        $result = $router->dispatch(new Request('POST', "/admin/teachers/{$this->teacherId}", [], [], [
            'age' => '99',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM teacher_profiles WHERE user_id = ?');
        $stmt->execute([$this->teacherId]);
        $this->assertSame('0', (string) $stmt->fetchColumn());
    }
}
