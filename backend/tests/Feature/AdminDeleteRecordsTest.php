<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Admin\CertificateController;
use App\Controllers\Admin\GroupController;
use App\Controllers\Admin\ProfessionController;
use App\Controllers\Admin\StudentController;
use App\Controllers\Admin\TeacherController;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminDeleteRecordsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM users WHERE phone IN ('+998987770280', '+998987770281')");
        $pdo->exec("DELETE FROM `groups` WHERE name = 'Deletable Group'");
    }

    public function testDeletingATeacherUnassignsTheirGroupsInsteadOfFailing(): void
    {
        $pdo = Database::pdo();
        $teacherId = (new UserRepository())->create('Deletable Teacher', '+998987770280', Auth::hashPassword('x'), 'teacher');
        $pdo->prepare('INSERT INTO `groups` (profession_id, teacher_id, name, start_date, end_date) VALUES (?, ?, "Deletable Group", "2026-01-01", "2026-02-01")')
            ->execute([$this->professionId, $teacherId]);
        $groupId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/teachers/{$teacherId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $teacherGone = $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$teacherId}")->fetchColumn();
        $this->assertSame(0, (int) $teacherGone);

        $groupTeacherId = $pdo->query("SELECT teacher_id FROM `groups` WHERE id = {$groupId}")->fetchColumn();
        $this->assertNull($groupTeacherId);
    }

    public function testDeletingAStudentRemovesTheirEnrollmentsAndAttempts(): void
    {
        $pdo = Database::pdo();
        $studentId = (new UserRepository())->create('Deletable Student', '+998987770281', Auth::hashPassword('x'), 'student');
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Deletable Group", "2026-01-01", "2026-02-01")')
            ->execute([$this->professionId]);
        $groupId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO enrollments (user_id, group_id, status) VALUES (?, ?, "active")')->execute([$studentId, $groupId]);
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "T", "T", 70)')->execute([$this->professionId]);
        $testId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 80, 1)')->execute([$studentId, $testId]);

        $router = new Router();
        (new StudentController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/students/{$studentId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$studentId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id = {$studentId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM test_attempts WHERE user_id = {$studentId}")->fetchColumn());
    }

    public function testDeletingAGroupRemovesItsScheduleAndEnrollments(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Deletable Group", "2026-01-01", "2026-02-01")')
            ->execute([$this->professionId]);
        $groupId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO schedule (group_id, lesson_date, start_time, end_time, room) VALUES (?, "2026-01-05", "09:00:00", "11:00:00", "101")')
            ->execute([$groupId]);

        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/groups/{$groupId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM `groups` WHERE id = {$groupId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM schedule WHERE group_id = {$groupId}")->fetchColumn());
    }

    public function testProfessionWithDependentGroupCannotBeDeleted(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Deletable Group", "2026-01-01", "2026-02-01")')
            ->execute([$this->professionId]);

        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$this->professionId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $stillExists = $pdo->query("SELECT COUNT(*) FROM professions WHERE id = {$this->professionId}")->fetchColumn();
        $this->assertSame(1, (int) $stillExists);
    }

    public function testProfessionWithOnlyAPendingApplicationCanStillBeDeleted(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price) VALUES ("Deletable Prof With App", "Deletable Prof With App", "d", "d", 10, 100)')
            ->execute();
        $professionId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, status) VALUES ("App Owner", "+998987770282", ?, "pending")')
            ->execute([$professionId]);
        $applicationId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$professionId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM professions WHERE id = {$professionId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE id = {$applicationId}")->fetchColumn());
    }

    public function testProfessionWithNoDependentsCanBeDeleted(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price) VALUES ("Deletable Prof", "Deletable Prof", "d", "d", 10, 100)')
            ->execute();
        $professionId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new ProfessionController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/professions/{$professionId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $stillExists = $pdo->query("SELECT COUNT(*) FROM professions WHERE id = {$professionId}")->fetchColumn();
        $this->assertSame(0, (int) $stillExists);
    }

    public function testDeletingACertificateRemovesItsRow(): void
    {
        $pdo = Database::pdo();
        $studentId = (new UserRepository())->create('Cert Owner', '+998987770281', Auth::hashPassword('x'), 'student');
        $pdo->prepare('INSERT INTO certificates (user_id, profession_id, certificate_number, issue_date, pdf_path) VALUES (?, ?, "CERT-TEST-1", "2026-01-01", "/tmp/does-not-exist.pdf")')
            ->execute([$studentId, $this->professionId]);
        $certificateId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new CertificateController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/certificates/{$certificateId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE id = {$certificateId}")->fetchColumn());
    }
}
