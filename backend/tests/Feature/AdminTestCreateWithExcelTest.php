<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TestController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class AdminTestCreateWithExcelTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec("DELETE FROM tests WHERE title_uz = 'Create With Excel'");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
    }

    protected function tearDown(): void
    {
        $_FILES = [];
    }

    private function makeUploadedFile(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Savol', 'A', 'B', '', '', 'To\'g\'ri javob'],
            ['1 + 1 nechiga teng?', '1', '2', '', '', '2'],
            ['2 + 2 nechiga teng?', '3', '4', '', '', '4'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tc_create_excel_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function testCreatingATestWithExcelImportsQuestionsAndSavesRegulations(): void
    {
        $path = $this->makeUploadedFile();
        $_FILES['excel_uz'] = ['name' => 'q.xlsx', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];

        $router = new Router();
        (new TestController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/tests', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'title_uz' => 'Create With Excel',
            'title_ru' => 'Создание с Excel',
            'passing_score' => '60',
            'random_question_count' => '1',
            'time_limit_minutes' => '20',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $this->assertStringContainsString('2 ta savol', $result['flash']);

        $pdo = Database::pdo();
        $test = $pdo->query("SELECT * FROM tests WHERE title_uz = 'Create With Excel'")->fetch();
        $this->assertNotFalse($test);
        $this->assertSame(1, (int) $test['random_question_count']);
        $this->assertSame(20, (int) $test['time_limit_minutes']);

        $questionCount = $pdo->query("SELECT COUNT(*) FROM questions WHERE test_id = {$test['id']}")->fetchColumn();
        $this->assertSame('2', (string) $questionCount);
    }
}
