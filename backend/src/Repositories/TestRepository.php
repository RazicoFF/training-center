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

    /**
     * A student gets exactly 2 attempts total at a test. The first attempt is free at any
     * time; if it fails, the second (and final) attempt only opens 14 days later - with no
     * expiry on that window. Failing the second attempt, or already having used both, means
     * the student has to re-enroll (and re-pay) to get another chance.
     *
     * @return array{eligible:bool, reason:string|null, availableAt:string|null}
     */
    public function retakeEligibility(int $userId, int $testId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT attempted_at FROM test_attempts WHERE user_id = ? AND test_id = ? ORDER BY attempted_at ASC'
        );
        $stmt->execute([$userId, $testId]);
        $attempts = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (count($attempts) === 0) {
            return ['eligible' => true, 'reason' => null, 'availableAt' => null];
        }

        if (count($attempts) >= 2) {
            return ['eligible' => false, 'reason' => 'max_attempts', 'availableAt' => null];
        }

        $firstAttempt = new \DateTimeImmutable((string) $attempts[0]);
        $retakeOpensAt = $firstAttempt->modify('+14 days');
        $now = new \DateTimeImmutable();

        if ($now < $retakeOpensAt) {
            return ['eligible' => false, 'reason' => 'too_early', 'availableAt' => $retakeOpensAt->format('Y-m-d H:i:s')];
        }

        return ['eligible' => true, 'reason' => null, 'availableAt' => null];
    }

    /**
     * True once a user has passed every test that exists for a profession - used to
     * automatically mark their enrollment(s) in that profession as completed.
     */
    public function allTestsPassedForProfession(int $userId, int $professionId): bool
    {
        $totalStmt = Database::pdo()->prepare('SELECT COUNT(*) FROM tests WHERE profession_id = ?');
        $totalStmt->execute([$professionId]);
        $total = (int) $totalStmt->fetchColumn();

        if ($total === 0) {
            return false;
        }

        $passedStmt = Database::pdo()->prepare(
            'SELECT COUNT(DISTINCT ta.test_id)
             FROM test_attempts ta
             JOIN tests t ON t.id = ta.test_id
             WHERE ta.user_id = ? AND ta.passed = 1 AND t.profession_id = ?'
        );
        $passedStmt->execute([$userId, $professionId]);
        $passed = (int) $passedStmt->fetchColumn();

        return $passed >= $total;
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

    /**
     * Picks the questions to show for one attempt: a random subset of size
     * random_question_count (or all questions, in random order, if that's not set),
     * each with its answers in random order too. When $userId is given, the exact
     * set of question ids shown is persisted (keyed by user+test) so score() can
     * later grade against exactly what this user was shown - regardless of whether
     * that call comes from the website (session-based) or the JWT-authenticated
     * mobile API (stateless), since both funnel through the same user id.
     */
    public function questionsWithAnswers(int $testId, ?int $userId = null): array
    {
        $test = $this->find($testId);
        $randomCount = ($test['random_question_count'] ?? null) !== null ? (int) $test['random_question_count'] : null;

        $pdo = Database::pdo();

        if ($randomCount !== null && $randomCount > 0) {
            $stmt = $pdo->prepare("SELECT id, text_uz, text_ru FROM questions WHERE test_id = ? ORDER BY RAND() LIMIT {$randomCount}");
            $stmt->execute([$testId]);
        } else {
            $stmt = $pdo->prepare('SELECT id, text_uz, text_ru FROM questions WHERE test_id = ?');
            $stmt->execute([$testId]);
        }
        $questions = $stmt->fetchAll();
        shuffle($questions);

        foreach ($questions as &$question) {
            $answerStmt = $pdo->prepare('SELECT id, text_uz, text_ru FROM answers WHERE question_id = ?');
            $answerStmt->execute([$question['id']]);
            $answers = $answerStmt->fetchAll();
            shuffle($answers);
            $question['answers'] = $answers;
        }
        unset($question);

        if ($userId !== null) {
            $this->persistQuestionSelection($userId, $testId, array_column($questions, 'id'));
        }

        return $questions;
    }

    private function persistQuestionSelection(int $userId, int $testId, array $questionIds): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO test_question_selections (user_id, test_id, question_ids) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE question_ids = VALUES(question_ids), created_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$userId, $testId, implode(',', $questionIds)]);
    }

    /**
     * @return int[]|null the exact question ids this user was last shown for this test,
     *                     or null if none was ever recorded (e.g. a test taken before this
     *                     feature existed)
     */
    private function shownQuestionIds(int $userId, int $testId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT question_ids FROM test_question_selections WHERE user_id = ? AND test_id = ?'
        );
        $stmt->execute([$userId, $testId]);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === '') {
            return null;
        }

        return array_map('intval', explode(',', $value));
    }

    /**
     * @param int[] $submittedAnswerIds one selected answer id per question
     * @return array{score:int, passed:bool}
     * @throws \InvalidArgumentException when a submitted answer does not belong to this test's
     *                                    questions, or two submitted answers belong to the same question
     */
    public function score(int $testId, int $userId, array $submittedAnswerIds): array
    {
        $pdo = Database::pdo();

        $shownQuestionIds = $this->shownQuestionIds($userId, $testId);

        if ($shownQuestionIds !== null) {
            $total = count($shownQuestionIds);
        } else {
            $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE test_id = ?');
            $totalStmt->execute([$testId]);
            $total = (int) $totalStmt->fetchColumn();
        }

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

        if ($shownQuestionIds !== null && array_diff($questionIds, $shownQuestionIds) !== []) {
            throw new \InvalidArgumentException('Submitted answers reference questions that were not shown to this user');
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
        ?string $closesAt = null,
        ?int $randomQuestionCount = null,
        ?int $timeLimitMinutes = null
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO tests (profession_id, title_uz, title_ru, passing_score, opens_at, closes_at, random_question_count, time_limit_minutes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $titleUz, $titleRu, $passingScore, $opensAt, $closesAt, $randomQuestionCount, $timeLimitMinutes]);

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
        ?string $closesAt = null,
        ?int $randomQuestionCount = null,
        ?int $timeLimitMinutes = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE tests SET title_uz = ?, title_ru = ?, passing_score = ?, opens_at = ?, closes_at = ?, random_question_count = ?, time_limit_minutes = ? WHERE id = ?'
        );
        $stmt->execute([$titleUz, $titleRu, $passingScore, $opensAt, $closesAt, $randomQuestionCount, $timeLimitMinutes, $id]);
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
