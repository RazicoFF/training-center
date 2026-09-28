<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\QuestionRepository;
use App\Services\TestExcelImporter;

final class QuestionController
{
    public function __construct(
        private readonly QuestionRepository $questions = new QuestionRepository(),
        private readonly TestExcelImporter $excelImporter = new TestExcelImporter()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests/{id}/questions', fn (Request $req) => $this->index($req));
        $router->post('/admin/tests/{id}/questions', fn (Request $req) => $this->store($req));
        $router->post('/admin/tests/{id}/questions/import', fn (Request $req) => $this->import($req));
        $router->get('/admin/tests/{id}/questions/{questionId}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/tests/{id}/questions/{questionId}', fn (Request $req) => $this->update($req));
        $router->post('/admin/tests/{id}/questions/{questionId}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');

        View::render('tests/questions', [
            'testId' => $testId,
            'questions' => $this->questions->forTestWithCorrectFlag($testId),
        ]);
        return ['rendered' => true];
    }

    private function store(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $textUz = trim((string) ($body['text_uz'] ?? ''));
        $textRu = trim((string) ($body['text_ru'] ?? ''));

        if ($textUz === '' || $textRu === '') {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $answers = $this->buildAnswersFromBody($body);
        if ($answers === null) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $this->questions->createWithAnswers($testId, $textUz, $textRu, $answers);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('question_added')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $question = $this->questions->find((int) $request->param('questionId'));

        if ($question === null || (int) $question['test_id'] !== $testId) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('tests/question_edit', ['testId' => $testId, 'question' => $question]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $questionId = (int) $request->param('questionId');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $existing = $this->questions->find($questionId);
        if ($existing === null || (int) $existing['test_id'] !== $testId) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $textUz = trim((string) ($body['text_uz'] ?? ''));
        $textRu = trim((string) ($body['text_ru'] ?? ''));

        if ($textUz === '' || $textRu === '') {
            return ['redirect' => "/admin/tests/{$testId}/questions/{$questionId}/edit", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $answers = $this->buildAnswersFromBody($body);
        if ($answers === null) {
            return ['redirect' => "/admin/tests/{$testId}/questions/{$questionId}/edit", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $this->questions->updateWithAnswers($questionId, $textUz, $textRu, $answers);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('question_updated')];
    }

    private function delete(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $questionId = (int) $request->param('questionId');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $existing = $this->questions->find($questionId);
        if ($existing === null || (int) $existing['test_id'] !== $testId) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $this->questions->delete($questionId);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('question_deleted')];
    }

    /**
     * Shared by store() and update(): the view always submits 4 answer_text_uz[]/
     * answer_text_ru[] pairs, but only the first 2 are marked required - filter out the
     * blank pairs so a 2-option question doesn't become 4 DB rows with empty text.
     *
     * @param array<string,mixed> $body
     * @return array<int, array{text_uz:string, text_ru:string, is_correct:bool}>|null
     *         null when fewer than 2 answers survive or none is marked correct
     */
    private function buildAnswersFromBody(array $body): ?array
    {
        $answerTextsUz = $body['answer_text_uz'] ?? [];
        $answerTextsRu = $body['answer_text_ru'] ?? [];
        $correctIndex = (int) ($body['correct_index'] ?? -1);

        $answers = [];
        foreach ($answerTextsUz as $i => $textUzAnswer) {
            $trimmedUz = trim((string) $textUzAnswer);
            if ($trimmedUz === '') {
                continue;
            }

            $answers[] = [
                'text_uz' => $trimmedUz,
                'text_ru' => trim((string) ($answerTextsRu[$i] ?? '')),
                'is_correct' => $i === $correctIndex,
            ];
        }

        $correctSurvived = array_filter($answers, static fn (array $a) => $a['is_correct']) !== [];

        return (count($answers) < 2 || !$correctSurvived) ? null : $answers;
    }

    private function import(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');

        $uzFile = $_FILES['excel_uz'] ?? null;
        if (!is_array($uzFile) || ($uzFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('excel_import_missing_uz')];
        }

        $ruFile = $_FILES['excel_ru'] ?? null;
        $ruPath = (is_array($ruFile) && ($ruFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
            ? (string) $ruFile['tmp_name']
            : null;

        try {
            $imported = $this->excelImporter->importFromFiles($testId, (string) $uzFile['tmp_name'], $ruPath);
        } catch (\Throwable) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('excel_import_failed')];
        }

        if ($imported === 0) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('excel_import_empty')];
        }

        return [
            'redirect' => "/admin/tests/{$testId}/questions",
            'flash' => sprintf(Lang::t('excel_import_success'), $imported),
        ];
    }
}
