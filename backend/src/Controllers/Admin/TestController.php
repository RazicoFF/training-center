<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ProfessionRepository;
use App\Repositories\TestRepository;
use App\Services\TestExcelImporter;

final class TestController
{
    public function __construct(
        private readonly TestRepository $tests = new TestRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly TestExcelImporter $excelImporter = new TestExcelImporter()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests', fn (Request $req) => $this->index($req));
        $router->get('/admin/tests/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/tests', fn (Request $req) => $this->create($req));
        $router->get('/admin/tests/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/tests/{id}/edit', fn (Request $req) => $this->update($req));
        $router->get('/admin/tests/{id}/attempts', fn (Request $req) => $this->attempts($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/index', ['tests' => $this->tests->allWithProfession()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/create', ['professions' => $this->professions->all()]);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) ($body['profession_id'] ?? 0);
        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));
        $passingScore = (int) ($body['passing_score'] ?? 70);

        if ($professionId <= 0 || $titleUz === '' || $titleRu === '') {
            return ['redirect' => '/admin/tests/create', 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $opensAt = $this->parseSchedule((string) ($body['opens_at'] ?? ''));
        $closesAt = $this->parseSchedule((string) ($body['closes_at'] ?? ''));
        $randomQuestionCount = ($body['random_question_count'] ?? '') !== '' ? (int) $body['random_question_count'] : null;
        $timeLimitMinutes = ($body['time_limit_minutes'] ?? '') !== '' ? (int) $body['time_limit_minutes'] : null;

        $testId = $this->tests->create(
            $professionId,
            $titleUz,
            $titleRu,
            $passingScore,
            $opensAt,
            $closesAt,
            $randomQuestionCount,
            $timeLimitMinutes
        );

        $importedCount = $this->importExcelIfProvided($testId);
        $flash = $importedCount !== null
            ? sprintf(Lang::t('excel_import_success'), $importedCount)
            : Lang::t('test_created');

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => $flash];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $test = $this->tests->find((int) $request->param('id'));

        if ($test === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('tests/edit', ['test' => $test]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        if ($this->tests->find($testId) === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));
        $passingScore = (int) ($body['passing_score'] ?? 70);

        if ($titleUz === '' || $titleRu === '') {
            return ['redirect' => "/admin/tests/{$testId}/edit", 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $opensAt = $this->parseSchedule((string) ($body['opens_at'] ?? ''));
        $closesAt = $this->parseSchedule((string) ($body['closes_at'] ?? ''));
        $randomQuestionCount = ($body['random_question_count'] ?? '') !== '' ? (int) $body['random_question_count'] : null;
        $timeLimitMinutes = ($body['time_limit_minutes'] ?? '') !== '' ? (int) $body['time_limit_minutes'] : null;

        $this->tests->update(
            $testId,
            $titleUz,
            $titleRu,
            $passingScore,
            $opensAt,
            $closesAt,
            $randomQuestionCount,
            $timeLimitMinutes
        );

        $importedCount = $this->importExcelIfProvided($testId);
        $flash = $importedCount !== null
            ? sprintf(Lang::t('excel_import_success'), $importedCount)
            : Lang::t('test_updated');

        return ['redirect' => '/admin/tests', 'flash' => $flash];
    }

    /**
     * Both the create and edit forms carry the same optional Excel-upload fields
     * (excel_uz required to trigger an import, excel_ru optional). Returns the
     * imported question count, or null if no file was uploaded at all.
     */
    private function importExcelIfProvided(int $testId): ?int
    {
        $uzFile = $_FILES['excel_uz'] ?? null;
        if (!is_array($uzFile) || ($uzFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $ruFile = $_FILES['excel_ru'] ?? null;
        $ruPath = (is_array($ruFile) && ($ruFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
            ? (string) $ruFile['tmp_name']
            : null;

        try {
            return $this->excelImporter->importFromFiles($testId, (string) $uzFile['tmp_name'], $ruPath);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function attempts(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $test = $this->tests->find((int) $request->param('id'));

        if ($test === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('tests/attempts', [
            'test' => $test,
            'attempts' => $this->tests->attemptsForTest((int) $test['id']),
        ]);
        return ['rendered' => true];
    }

    /**
     * The <input type="datetime-local"> value comes in as "Y-m-d\TH:i"; converts it to the
     * "Y-m-d H:i:s" format MySQL DATETIME columns expect, or null when left blank.
     */
    private function parseSchedule(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        return $date === false ? null : $date->format('Y-m-d H:i:s');
    }
}
