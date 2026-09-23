<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\CertificateRepository;

final class CertificateController
{
    public function __construct(private readonly CertificateRepository $repository = new CertificateRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/certificates', fn (Request $req) => $this->index($req));
        $router->get('/admin/certificates/{id}/download', fn (Request $req) => $this->download($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('certificates/index', ['certificates' => $this->repository->allWithDetails()]);
        return ['rendered' => true];
    }

    private function download(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
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
}
