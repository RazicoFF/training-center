<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\StudentController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminStudentDetailTest extends TestCase
{
    private int $studentId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM test_attempts WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770130')");
        $pdo->exec("DELETE FROM certificates WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770130')");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770130'");

        $this->studentId = (new UserRepository())->create('Detail Student', '+998987770130', 'x', 'student');

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "Detail Test", "Тест деталей", 70)')
            ->execute([$professionId]);
        $testId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 85, 1)')
            ->execute([$this->studentId, $testId]);

        $pdo->prepare(
            "INSERT INTO certificates (user_id, profession_id, certificate_number, issue_date, pdf_path)
             VALUES (?, ?, 'CERT-DETAIL-TEST', '2026-03-01', '/tmp/x.pdf')"
        )->execute([$this->studentId, $professionId]);
    }

    protected function tearDown(): void
    {
        // AdminTestsTest's setUp does an unconditional `DELETE FROM tests`, which would
        // otherwise fail on the FK from the test_attempts row this test creates.
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM test_attempts WHERE user_id = {$this->studentId}");
        $pdo->exec('DELETE FROM tests WHERE title_uz = "Detail Test"');
    }

    public function testEditPageShowsTestResultsAndCertificates(): void
    {
        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/admin/students/{$this->studentId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Detail Test', $html);
        $this->assertStringContainsString('85%', $html);
        $this->assertStringContainsString('CERT-DETAIL-TEST', $html);
    }

    public function testStudentsIndexShowsCertificateDownloadButton(): void
    {
        $certificateId = (int) Database::pdo()->query("SELECT id FROM certificates WHERE certificate_number = 'CERT-DETAIL-TEST'")->fetchColumn();

        $router = new Router();
        (new StudentController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/students', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString("/admin/certificates/{$certificateId}/download", $html);
    }
}
