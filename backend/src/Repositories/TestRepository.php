<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TestRepository
{
    public function availableForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.title_uz, t.title_ru, t.passing_score
             FROM tests t
             JOIN professions p ON p.id = t.profession_id
             JOIN `groups` g ON g.profession_id = p.id
             JOIN enrollments e ON e.group_id = g.id
             WHERE e.user_id = ? AND e.status = 'active'
               AND t.id NOT IN (SELECT test_id FROM test_attempts WHERE user_id = ? AND passed = 1)
             GROUP BY t.id"
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    public function questionsWithAnswers(int $testId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM questions WHERE test_id = ?');
        $stmt->execute([$testId]);
        $questions = $stmt->fetchAll();

        foreach ($questions as &$question) {
            $answerStmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM answers WHERE question_id = ?');
            $answerStmt->execute([$question['id']]);
            $question['answers'] = $answerStmt->fetchAll();
        }

        return $questions;
    }

    /**
     * @param int[] $submittedAnswerIds one selected answer id per question
     * @return array{score:int, passed:bool}
     */
    public function score(int $testId, array $submittedAnswerIds): array
    {
        $pdo = Database::pdo();

        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE test_id = ?');
        $totalStmt->execute([$testId]);
        $total = (int) $totalStmt->fetchColumn();

        if ($total === 0 || $submittedAnswerIds === []) {
            return ['score' => 0, 'passed' => false];
        }

        $placeholders = implode(',', array_fill(0, count($submittedAnswerIds), '?'));
        $correctStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM answers WHERE id IN ({$placeholders}) AND is_correct = 1"
        );
        $correctStmt->execute($submittedAnswerIds);
        $correct = (int) $correctStmt->fetchColumn();

        $passingScoreStmt = $pdo->prepare('SELECT passing_score FROM tests WHERE id = ?');
        $passingScoreStmt->execute([$testId]);
        $passingScore = (int) $passingScoreStmt->fetchColumn();

        $score = (int) round(($correct / $total) * 100);

        return ['score' => $score, 'passed' => $score >= $passingScore];
    }

    public function recordAttempt(int $userId, int $testId, int $score, bool $passed): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $testId, $score, $passed ? 1 : 0]);

        return (int) Database::pdo()->lastInsertId();
    }
}
