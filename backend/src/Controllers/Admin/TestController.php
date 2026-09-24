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

final class TestController
{
    public function __construct(
        private readonly TestRepository $tests = new TestRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests', fn (Request $req) => $this->index($req));
        $router->get('/admin/tests/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/tests', fn (Request $req) => $this->create($req));
        $router->get('/admin/tests/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/tests/{id}/edit', fn (Request $req) => $this->update($req));
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

        $testId = $this->tests->create($professionId, $titleUz, $titleRu, $passingScore);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('test_created')];
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

        $this->tests->update($testId, $titleUz, $titleRu, $passingScore);

        return ['redirect' => '/admin/tests', 'flash' => Lang::t('test_updated')];
    }
}
