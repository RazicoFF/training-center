<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\CertificateRepository;
use App\Repositories\ProfessionRepository;

final class CertificateController
{
    public function __construct(
        private readonly CertificateRepository $repository = new CertificateRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/certificates', fn (Request $req) => $this->index($req));
        $router->get('/admin/certificates/{id}/download', fn (Request $req) => $this->download($req));
        $router->post('/admin/certificates/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $professionId = ($_GET['profession_id'] ?? '') !== '' ? (int) $_GET['profession_id'] : null;
        $year = ($_GET['year'] ?? '') !== '' ? (int) $_GET['year'] : null;

        View::render('certificates/index', [
            'certificates' => $this->repository->allWithDetails($q !== '' ? $q : null, $professionId, $year),
            'professions' => $this->professions->all(),
            'years' => $this->repository->distinctYears(),
            'q' => $q,
            'professionId' => $professionId,
            'year' => $year,
        ]);
        return ['rendered' => true];
    }

    private function download(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $certificate = $this->repository->find((int) $request->param('id'));

        if ($certificate === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        return ['file' => $certificate['pdf_path']];
    }

    private function delete(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $certificateId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        if ($this->repository->find($certificateId) === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $this->repository->delete($certificateId);

        return ['redirect' => '/admin/certificates', 'flash' => Lang::t('certificate_deleted')];
    }
}
