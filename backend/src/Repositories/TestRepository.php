<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TestRepository
{
    public function availableForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.title_uz, t.title_ru, t.passing_score, t.opens_at, t.closes_at
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

    /**
     * A test with no opens_at/closes_at is always open (backward-compatible default
     * for tests created before scheduling existed).
     */
    public function isOpenNow(array $test): bool
    {
        $now = new \DateTimeImmutable();

        if (!empty($test['opens_at']) && $now < new \DateTimeImmutable((string) $test['opens_at'])) {
            return false;
        }

        if (!empty($test['closes_at']) && $now > new \DateTimeImmutable((string) $test['closes_at'])) {
            return false;
        }

        return true;
    }

    public function attemptsForTest(int $testId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ta.id, ta.score, ta.passed, ta.attempted_at, u.full_name, u.phone
             FROM test_attempts ta
             JOIN users u ON u.id = ta.user_id
             WHERE ta.test_id = ?
             ORDER BY ta.attempted_at DESC'
        );
        $stmt->execute([$testId]);

        return $stmt->fetchAll();
    }

    public function attemptsForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ta.id, ta.score, ta.passed, ta.attempted_at, t.title_uz, t.title_ru
             FROM test_attempts ta
             JOIN tests t ON t.id = ta.test_id
             WHERE ta.user_id = ?
             ORDER BY ta.attempted_at DESC'
        );
        $stmt->execute([$userId]);

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
     * @throws \InvalidArgumentException when a submitted answer does not belong to this test's
     *                                    questions, or two submitted answers belong to the same question
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
        $ownershipStmt = $pdo->prepare(
            "SELECT a.id, a.question_id, a.is_correct
             FROM answers a
             JOIN questions q ON q.id = a.question_id
             WHERE a.id IN ({$placeholders}) AND q.test_id = ?"
        );
        $ownershipStmt->execute([...$submittedAnswerIds, $testId]);
        $rows = $ownershipStmt->fetchAll();

        if (count($rows) !== count($submittedAnswerIds)) {
            throw new \InvalidArgumentException(
                'One or more submitted answers do not belong to a question of this test'
            );
        }

        $questionIds = array_map(static fn (array $row): int => (int) $row['question_id'], $rows);
        if (count($questionIds) !== count(array_unique($questionIds))) {
            throw new \InvalidArgumentException('Only one answer per question may be submitted');
        }

        $correct = 0;
        foreach ($rows as $row) {
            if ((int) $row['is_correct'] === 1) {
                $correct++;
            }
        }

        $passingScoreStmt = $pdo->prepare('SELECT passing_score FROM tests WHERE id = ?');
        $passingScoreStmt->execute([$testId]);
        $passingScore = (int) $passingScoreStmt->fetchColumn();

        $score = (int) round(($correct / $total) * 100);
        $score = max(0, min(100, $score));

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

    public function create(
        int $professionId,
        string $titleUz,
        string $titleRu,
        int $passingScore,
        ?string $opensAt = null,
        ?string $closesAt = null
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO tests (profession_id, title_uz, title_ru, passing_score, opens_at, closes_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $titleUz, $titleRu, $passingScore, $opensAt, $closesAt]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM tests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function update(
        int $id,
        string $titleUz,
        string $titleRu,
        int $passingScore,
        ?string $opensAt = null,
        ?string $closesAt = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE tests SET title_uz = ?, title_ru = ?, passing_score = ?, opens_at = ?, closes_at = ? WHERE id = ?'
        );
        $stmt->execute([$titleUz, $titleRu, $passingScore, $opensAt, $closesAt, $id]);
    }

    /**
     * Tests belonging to one profession, for the public profession detail page
     * (informational only - titles and question counts, no answers).
     */
    public function forProfession(int $professionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.id, t.title_uz, t.title_ru,
                    (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) AS question_count
             FROM tests t WHERE t.profession_id = ? ORDER BY t.id'
        );
        $stmt->execute([$professionId]);

        return $stmt->fetchAll();
    }

    public function allWithProfession(): array
    {
        $sql = 'SELECT t.*, p.name_uz AS profession_name_uz,
                       (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) AS question_count
                FROM tests t JOIN professions p ON p.id = t.profession_id
                ORDER BY p.name_uz, t.id';

        return Database::pdo()->query($sql)->fetchAll();
    }
}
