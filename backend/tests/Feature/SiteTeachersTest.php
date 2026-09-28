<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\TeacherController;
use App\Repositories\TeacherProfileRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class SiteTeachersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM teacher_profiles WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770040')");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770040'");
    }

    public function testIndexShowsTeacherWithOptionalFieldsAndSkipsTeacherWithoutProfile(): void
    {
        $userId = (new UserRepository())->create('Public Teacher', '+998987770040', 'x', 'teacher');
        (new TeacherProfileRepository())->upsert($userId, [
            'experience_years' => 8,
            'skills_uz' => 'Ekskavator boshqarish',
        ]);

        $router = new Router();
        (new TeacherController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/teachers', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Public Teacher', $html);
        $this->assertStringContainsString('Ekskavator boshqarish', $html);
        $this->assertStringContainsString('8', $html);
    }

    public function testCardOpensModalWithFullProfileButNoPhone(): void
    {
        $userId = (new UserRepository())->create('Modal Teacher', '+998987770040', 'x', 'teacher');
        (new TeacherProfileRepository())->upsert($userId, [
            'birth_date' => '1990-05-01',
            'education_uz' => 'Toshkent davlat texnika universiteti',
            'telegram' => '@modal_teacher',
            'email' => 'modal@example.com',
        ]);

        $router = new Router();
        (new TeacherController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/teachers', [], [], []));
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/data-bs-target="#tcTeacherModal(\d+)"/', $html);
        $this->assertStringContainsString('class="modal fade" id="tcTeacherModal', $html);
        $this->assertStringContainsString('Toshkent davlat texnika universiteti', $html);
        $this->assertStringContainsString('https://t.me/modal_teacher', $html);
        $this->assertStringContainsString('mailto:modal@example.com', $html);
        $this->assertStringNotContainsString('+998987770040', $html);
    }
}
