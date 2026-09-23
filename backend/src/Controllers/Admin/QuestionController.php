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

final class QuestionController
{
    public function __construct(private readonly QuestionRepository $questions = new QuestionRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests/{id}/questions', fn (Request $req) => $this->index($req));
        $router->post('/admin/tests/{id}/questions', fn (Request $req) => $this->store($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
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
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $textUz = trim((string) ($body['text_uz'] ?? ''));
        $textRu = trim((string) ($body['text_ru'] ?? ''));
        $answerTextsUz = $body['answer_text_uz'] ?? [];
        $answerTextsRu = $body['answer_text_ru'] ?? [];
        $correctIndex = (int) ($body['correct_index'] ?? -1);

        if ($textUz === '' || $textRu === '' || count($answerTextsUz) < 2 || $correctIndex < 0 || $correctIndex >= count($answerTextsUz)) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $answers = [];
        foreach ($answerTextsUz as $i => $textUzAnswer) {
            $answers[] = [
                'text_uz' => (string) $textUzAnswer,
                'text_ru' => (string) ($answerTextsRu[$i] ?? ''),
                'is_correct' => $i === $correctIndex,
            ];
        }

        $this->questions->createWithAnswers($testId, $textUz, $textRu, $answers);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('question_added')];
    }
}
