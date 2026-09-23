<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class QuestionRepository
{
    /**
     * @param array<int, array{text_uz:string, text_ru:string, is_correct:bool}> $answers
     */
    public function createWithAnswers(int $testId, string $textUz, string $textRu, array $answers): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('INSERT INTO questions (test_id, text_uz, text_ru) VALUES (?, ?, ?)');
            $stmt->execute([$testId, $textUz, $textRu]);
            $questionId = (int) $pdo->lastInsertId();

            $answerStmt = $pdo->prepare(
                'INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, ?, ?, ?)'
            );
            foreach ($answers as $answer) {
                $answerStmt->execute([$questionId, $answer['text_uz'], $answer['text_ru'], $answer['is_correct'] ? 1 : 0]);
            }

            $pdo->commit();

            return $questionId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function forTestWithCorrectFlag(int $testId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM questions WHERE test_id = ?');
        $stmt->execute([$testId]);
        $questions = $stmt->fetchAll();

        foreach ($questions as &$question) {
            $answerStmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru, is_correct FROM answers WHERE question_id = ?');
            $answerStmt->execute([$question['id']]);
            $question['answers'] = $answerStmt->fetchAll();
        }

        return $questions;
    }
}
