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

    /**
     * @return array{id:int, test_id:int, text_uz:string, text_ru:string, answers:array}|null
     */
    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, test_id, text_uz, text_ru FROM questions WHERE id = ?');
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if ($question === false) {
            return null;
        }

        $answerStmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru, is_correct FROM answers WHERE question_id = ?');
        $answerStmt->execute([$id]);
        $question['answers'] = $answerStmt->fetchAll();

        return $question;
    }

    /**
     * @param array<int, array{text_uz:string, text_ru:string, is_correct:bool}> $answers
     */
    public function updateWithAnswers(int $questionId, string $textUz, string $textRu, array $answers): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('UPDATE questions SET text_uz = ?, text_ru = ? WHERE id = ?')
                ->execute([$textUz, $textRu, $questionId]);

            // Simplest correct way to replace a question's answer set: delete the old rows
            // and insert the new ones, rather than trying to diff/update in place.
            $pdo->prepare('DELETE FROM answers WHERE question_id = ?')->execute([$questionId]);

            $answerStmt = $pdo->prepare(
                'INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, ?, ?, ?)'
            );
            foreach ($answers as $answer) {
                $answerStmt->execute([$questionId, $answer['text_uz'], $answer['text_ru'], $answer['is_correct'] ? 1 : 0]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM answers WHERE question_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
    }
}
