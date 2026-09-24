<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QuestionRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bulk-imports test questions from an Excel file: column A holds the question text,
 * columns B-E hold up to four answer options, and column F holds the correct answer's
 * text (matched, case-insensitively, against one of B-E on the same row). Row 1 is
 * treated as a header and skipped.
 *
 * An optional second file provides the Russian translation, aligned row-for-row and
 * column-for-column with the required (Uzbek) file. Any Russian cell left blank falls
 * back to its Uzbek counterpart, since questions/answers.text_ru is NOT NULL.
 */
final class TestExcelImporter
{
    public function __construct(private readonly QuestionRepository $questions = new QuestionRepository())
    {
    }

    public function importFromFiles(int $testId, string $uzFilePath, ?string $ruFilePath = null): int
    {
        $uzRows = $this->readRows($uzFilePath);
        $ruRows = $ruFilePath !== null ? $this->readRows($ruFilePath) : [];

        $imported = 0;

        foreach ($uzRows as $index => $row) {
            $textUz = trim((string) ($row[0] ?? ''));
            if ($textUz === '') {
                continue;
            }

            $ruRow = $ruRows[$index] ?? [];
            $textRu = trim((string) ($ruRow[0] ?? '')) ?: $textUz;

            $correctText = mb_strtolower(trim((string) ($row[5] ?? '')));

            $answers = [];
            foreach ([1, 2, 3, 4] as $col) {
                $optionUz = trim((string) ($row[$col] ?? ''));
                if ($optionUz === '') {
                    continue;
                }

                $optionRu = trim((string) ($ruRow[$col] ?? '')) ?: $optionUz;

                $answers[] = [
                    'text_uz' => $optionUz,
                    'text_ru' => $optionRu,
                    'is_correct' => $correctText !== '' && mb_strtolower($optionUz) === $correctText,
                ];
            }

            $hasCorrect = array_filter($answers, static fn (array $a) => $a['is_correct']) !== [];

            if (count($answers) < 2 || !$hasCorrect) {
                continue;
            }

            $this->questions->createWithAnswers($testId, $textUz, $textRu, $answers);
            $imported++;
        }

        return $imported;
    }

    /**
     * @return array<int, array<int, string>> zero-indexed rows and columns, header row removed
     */
    private function readRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        array_shift($rows);

        return array_values($rows);
    }
}
