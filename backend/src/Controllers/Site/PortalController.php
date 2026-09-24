<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Middleware\SiteAuthMiddleware;
use App\Repositories\CertificateRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TestRepository;
use App\Repositories\UserRepository;

final class PortalController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly ScheduleRepository $schedule = new ScheduleRepository(),
        private readonly TestRepository $tests = new TestRepository(),
        private readonly CertificateRepository $certificates = new CertificateRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/portal', fn (Request $req) => $this->dashboard($req));
        $router->get('/portal/schedule', fn (Request $req) => $this->schedule($req));
        $router->get('/portal/tests', fn (Request $req) => $this->testsIndex($req));
        $router->get('/portal/tests/{id}', fn (Request $req) => $this->testShow($req));
        $router->post('/portal/tests/{id}/submit', fn (Request $req) => $this->testSubmit($req));
        $router->get('/portal/certificates', fn (Request $req) => $this->certificatesIndex($req));
        $router->get('/portal/certificates/{id}/download', fn (Request $req) => $this->certificateDownload($req));
    }

    private function dashboard(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        $user = $this->users->find($claims['user_id']);

        SiteView::render('site/portal/dashboard', ['user' => $user]);
        return ['rendered' => true];
    }

    private function schedule(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        SiteView::render('site/portal/schedule', ['lessons' => $this->schedule->forUser($claims['user_id'])]);
        return ['rendered' => true];
    }

    private function testsIndex(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        SiteView::render('site/portal/tests_index', ['tests' => $this->tests->availableForUser($claims['user_id'])]);
        return ['rendered' => true];
    }

    private function testShow(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        $testId = (int) $request->param('id');

        SiteView::render('site/portal/test_show', [
            'testId' => $testId,
            'questions' => $this->tests->questionsWithAnswers($testId),
        ]);
        return ['rendered' => true];
    }

    private function testSubmit(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/portal/tests'];
        }

        $testId = (int) $request->param('id');
        $answerIds = array_map('intval', array_values($body['answer_id'] ?? []));

        try {
            $result = $this->tests->score($testId, $answerIds);
        } catch (\InvalidArgumentException) {
            return ['redirect' => '/portal/tests', 'flash' => 'Test yuborishda xatolik yuz berdi'];
        }

        $this->tests->recordAttempt($claims['user_id'], $testId, $result['score'], $result['passed']);

        if ($result['passed']) {
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

                    $this->certificates->issue($claims['user_id'], $professionId);
                }
            } catch (\Throwable $e) {
                error_log('Certificate issuance failed: ' . $e->getMessage());
            }
        }

        SiteView::render('site/portal/test_result', ['score' => $result['score'], 'passed' => $result['passed']]);
        return ['rendered' => true];
    }

    private function certificatesIndex(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        SiteView::render('site/portal/certificates', ['certificates' => $this->certificates->forUser($claims['user_id'])]);
        return ['rendered' => true];
    }

    private function certificateDownload(Request $request): array
    {
        $claims = SiteAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/login'];
        }

        $certificate = $this->certificates->find((int) $request->param('id'));

        if ($certificate === null || (int) $certificate['user_id'] !== $claims['user_id']) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        return ['file' => $certificate['pdf_path']];
    }
}
