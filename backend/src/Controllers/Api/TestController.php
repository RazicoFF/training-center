<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\CertificateRepository;
use App\Repositories\GroupRepository;
use App\Repositories\TestRepository;

final class TestController
{
    public function __construct(
        private readonly TestRepository $repository = new TestRepository(),
        private readonly GroupRepository $groups = new GroupRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me/tests', fn (Request $req) => $this->index($req));
        $router->get('/api/v1/me/tests/{id}', fn (Request $req) => $this->show($req));
        $router->post('/api/v1/me/tests/{id}/submit', fn (Request $req) => $this->submit($req));
    }

    private function index(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['tests' => $this->repository->availableForUser($claims['user_id'])];
    }

    private function show(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $testId = (int) $request->param('id');

        $eligibility = $this->repository->retakeEligibility($claims['user_id'], $testId);
        if (!$eligibility['eligible']) {
            return ['error' => ['code' => 'TEST_NOT_RETAKEABLE', 'message' => $eligibility['reason']], 'status' => 403];
        }

        return ['questions' => $this->repository->questionsWithAnswers($testId, $claims['user_id'])];
    }

    private function submit(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $testId = (int) $request->param('id');

        $eligibility = $this->repository->retakeEligibility($claims['user_id'], $testId);
        if (!$eligibility['eligible']) {
            return ['error' => ['code' => 'TEST_NOT_RETAKEABLE', 'message' => $eligibility['reason']], 'status' => 403];
        }

        $answers = $request->jsonBody()['answers'] ?? [];

        if (!is_array($answers)) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'answers must be an array of answer ids'],
                'status' => 422,
            ];
        }

        $answerIds = array_map('intval', $answers);

        try {
            $result = $this->repository->score($testId, $claims['user_id'], $answerIds);
        } catch (\InvalidArgumentException $e) {
            return ['error' => ['code' => 'VALIDATION_ERROR', 'message' => $e->getMessage()], 'status' => 422];
        }

        $this->repository->recordAttempt($claims['user_id'], $testId, $result['score'], $result['passed']);

        if ($result['passed']) {
            // Certificate issuance is a side effect of a successful submission, not the
            // submission itself: the attempt is already recorded and scored above, so a
            // failure here (PDF generation, DB error) must not turn this into a 500.
            try {
                $alreadyIssuedStmt = Database::pdo()->prepare(
                    'SELECT COUNT(*) FROM certificates c
                     JOIN tests t ON t.profession_id = c.profession_id
                     WHERE c.user_id = ? AND t.id = ?'
                );
                $alreadyIssuedStmt->execute([$claims['user_id'], $testId]);

                if ((int) $alreadyIssuedStmt->fetchColumn() === 0) {
                    $professionStmt = Database::pdo()->prepare('SELECT profession_id FROM tests WHERE id = ?');
                    $professionStmt->execute([$testId]);
                    $professionId = (int) $professionStmt->fetchColumn();

                    (new CertificateRepository())->issue($claims['user_id'], $professionId);
                }
            } catch (\Throwable $e) {
                error_log('Certificate issuance failed: ' . $e->getMessage());
            }

            $professionStmt = Database::pdo()->prepare('SELECT profession_id FROM tests WHERE id = ?');
            $professionStmt->execute([$testId]);
            $professionId = (int) $professionStmt->fetchColumn();

            if ($this->repository->allTestsPassedForProfession($claims['user_id'], $professionId)) {
                $this->groups->completeEnrollmentsForProfession($claims['user_id'], $professionId);
            }
        }

        return $result;
    }
}
