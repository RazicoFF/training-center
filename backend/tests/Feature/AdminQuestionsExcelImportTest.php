<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\QuestionController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class AdminQuestionsExcelImportTest extends TestCase
{
    private int $testId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "Import Endpoint Test", "Тест эндпоинта", 70)')
            ->execute([$professionId]);
        $this->testId = (int) $pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        $_FILES = [];
    }

    private function makeUploadedFile(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Savol', 'A', 'B', '', '', 'To\'g\'ri javob'],
            ['1 + 1 nechiga teng?', '1', '2', '', '', '2'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tc_excel_upload_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function testImportEndpointCreatesQuestionsFromUploadedFile(): void
    {
        $path = $this->makeUploadedFile();
        $_FILES['excel_uz'] = ['name' => 'test.xlsx', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];

        $router = new Router();
        (new QuestionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/questions/import", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame([
            'redirect' => "/admin/tests/{$this->testId}/questions",
            'flash' => "1 ta savol muvaffaqiyatli qo'shildi",
        ], $result);

        $question = Database::pdo()->query("SELECT text_uz FROM questions WHERE test_id = {$this->testId}")->fetch();
        $this->assertSame('1 + 1 nechiga teng?', $question['text_uz']);
    }

    public function testImportEndpointRejectsMissingCsrfToken(): void
    {
        $path = $this->makeUploadedFile();
        $_FILES['excel_uz'] = ['name' => 'test.xlsx', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];

        $router = new Router();
        (new QuestionController())->register($router);

        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/questions/import", [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);

        $count = Database::pdo()->query("SELECT COUNT(*) FROM questions WHERE test_id = {$this->testId}")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }
}
