<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\TestExcelImporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class TestExcelImporterTest extends TestCase
{
    private int $testId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "Excel Import Test", "Тест импорта", 70)')
            ->execute([$professionId]);
        $this->testId = (int) $pdo->lastInsertId();
    }

    private function writeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'tc_excel_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function testImportsQuestionsFromUzOnlyWorkbook(): void
    {
        $path = $this->writeWorkbook([
            ['Savol', 'A', 'B', 'C', 'D', 'To\'g\'ri javob'],
            ['2 + 2 nechiga teng?', '3', '4', '5', '6', '4'],
            ['Ekskavator nima?', 'Mashina', 'Meva', '', '', 'Mashina'],
        ]);

        $imported = (new TestExcelImporter())->importFromFiles($this->testId, $path);

        $this->assertSame(2, $imported);

        $pdo = Database::pdo();
        $questions = $pdo->query("SELECT id, text_uz, text_ru FROM questions WHERE test_id = {$this->testId} ORDER BY id")->fetchAll();
        $this->assertCount(2, $questions);
        $this->assertSame('2 + 2 nechiga teng?', $questions[0]['text_uz']);
        $this->assertSame('2 + 2 nechiga teng?', $questions[0]['text_ru']);

        $answers = $pdo->query("SELECT text_uz, is_correct FROM answers WHERE question_id = {$questions[0]['id']} ORDER BY id")->fetchAll();
        $this->assertCount(4, $answers);
        $this->assertSame(0, (int) $answers[0]['is_correct']);
        $this->assertSame(1, (int) $answers[1]['is_correct']);

        unlink($path);
    }

    public function testImportUsesRussianWorkbookWhenProvided(): void
    {
        $uzPath = $this->writeWorkbook([
            ['Savol', 'A', 'B', '', '', 'To\'g\'ri javob'],
            ['Ekskavator nima?', 'Mashina', 'Meva', '', '', 'Mashina'],
        ]);
        $ruPath = $this->writeWorkbook([
            ['Вопрос', 'А', 'Б', '', '', 'Правильный ответ'],
            ['Что такое экскаватор?', 'Машина', 'Фрукт', '', '', 'Машина'],
        ]);

        $imported = (new TestExcelImporter())->importFromFiles($this->testId, $uzPath, $ruPath);
        $this->assertSame(1, $imported);

        $pdo = Database::pdo();
        $question = $pdo->query("SELECT text_uz, text_ru FROM questions WHERE test_id = {$this->testId}")->fetch();
        $this->assertSame('Ekskavator nima?', $question['text_uz']);
        $this->assertSame('Что такое экскаватор?', $question['text_ru']);

        unlink($uzPath);
        unlink($ruPath);
    }

    public function testRowsWithoutAMatchingCorrectAnswerAreSkipped(): void
    {
        $path = $this->writeWorkbook([
            ['Savol', 'A', 'B', '', '', 'To\'g\'ri javob'],
            ['Noaniq savol', 'X', 'Y', '', '', 'Z'],
        ]);

        $imported = (new TestExcelImporter())->importFromFiles($this->testId, $path);
        $this->assertSame(0, $imported);

        unlink($path);
    }
}
