<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\GroupController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\ProfessionController;
use App\Controllers\Admin\StudentController;
use App\Controllers\Admin\TeacherController;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class TeacherScopeTest extends TestCase
{
    private int $ownTeacherId;
    private int $otherTeacherId;
    private int $ownGroupId;
    private int $otherGroupId;
    private int $ownStudentId;
    private int $otherStudentId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM enrollments WHERE user_id IN (SELECT id FROM users WHERE phone IN ('+998987770240', '+998987770241'))");
        $pdo->exec("DELETE FROM `groups` WHERE name IN ('Own Teacher Group', 'Other Teacher Group')");
        $pdo->exec("DELETE FROM users WHERE phone IN ('+998987770242', '+998987770243', '+998987770240', '+998987770241')");

        $users = new UserRepository();
        $this->ownTeacherId = $users->create('Own Teacher', '+998987770242', Auth::hashPassword('teachpass1'), 'teacher');
        $this->otherTeacherId = $users->create('Other Teacher', '+998987770243', Auth::hashPassword('teachpass1'), 'teacher');
        $this->ownStudentId = $users->create('Own Student', '+998987770240', 'x', 'student');
        $this->otherStudentId = $users->create('Other Student', '+998987770241', 'x', 'student');

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, teacher_id, name, start_date, end_date) VALUES (?, ?, "Own Teacher Group", "2026-01-01", "2026-06-01")')
            ->execute([$professionId, $this->ownTeacherId]);
        $this->ownGroupId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO `groups` (profession_id, teacher_id, name, start_date, end_date) VALUES (?, ?, "Other Teacher Group", "2026-01-01", "2026-06-01")')
            ->execute([$professionId, $this->otherTeacherId]);
        $this->otherGroupId = (int) $pdo->lastInsertId();

        $groups = new GroupRepository();
        $groups->enrollStudent($this->ownGroupId, $this->ownStudentId);
        $groups->enrollStudent($this->otherGroupId, $this->otherStudentId);

        $_SESSION = ['admin_user_id' => $this->ownTeacherId, 'admin_role' => 'teacher'];
    }

    public function testGroupsIndexOnlyShowsOwnGroups(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/admin/groups', [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('Own Teacher Group', $html);
        $this->assertStringNotContainsString('Other Teacher Group', $html);
    }

    public function testCannotViewAnotherTeachersGroup(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', "/admin/groups/{$this->otherGroupId}", [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('404', $html);
    }

    public function testCanViewOwnGroup(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/groups/{$this->ownGroupId}", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Own Student', $html);
    }

    public function testStudentsIndexOnlyShowsOwnStudents(): void
    {
        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/admin/students', [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('Own Student', $html);
        $this->assertStringNotContainsString('Other Student', $html);
    }

    public function testCannotOpenAnotherTeachersStudentEditPage(): void
    {
        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', "/admin/students/{$this->otherStudentId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('404', $html);
    }

    public function testCanOpenOwnStudentEditPage(): void
    {
        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/students/{$this->ownStudentId}/edit", [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testDashboardRedirectsTeacherToGroups(): void
    {
        $router = new Router();
        (new DashboardController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin', [], [], []));

        $this->assertSame(['redirect' => '/admin/groups'], $result);
    }

    public function testTeacherCannotViewOtherTeachersList(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/teachers', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }

    public function testTeacherCannotViewProfessionsAdmin(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/professions', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }

    public function testTeacherCannotViewNewsAdmin(): void
    {
        $router = new Router();
        (new NewsController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/news', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }

    public function testTeacherCannotViewMediaAdmin(): void
    {
        $router = new Router();
        (new MediaController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin/media', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
