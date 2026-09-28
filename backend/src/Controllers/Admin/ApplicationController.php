<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ApplicationRepository;

final class ApplicationController
{
    public function __construct(private readonly ApplicationRepository $repository = new ApplicationRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/applications', fn (Request $req) => $this->index($req));
        $router->get('/admin/applications/{id}', fn (Request $req) => $this->show($req));
        $router->post('/admin/applications/{id}/approve', fn (Request $req) => $this->approve($req));
        $router->post('/admin/applications/{id}/reject', fn (Request $req) => $this->reject($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $status = $_GET['status'] ?? null;
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : null;
        $q = trim((string) ($_GET['q'] ?? ''));

        View::render('applications/index', [
            'applications' => $this->repository->allWithProfession($status, $q !== '' ? $q : null),
            'statusFilter' => $status,
            'statusCounts' => $this->repository->countsByStatus(),
            'q' => $q,
        ]);

        return ['rendered' => true];
    }

    /**
     * Application card: large photo, contacts, chosen profession/brand, and the
     * approve/reject actions. With ?modal=1 only the card fragment is returned.
     */
    private function show(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $application = $this->repository->findWithProfession((int) $request->param('id'));
        if ($application === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        if (($_GET['modal'] ?? '') === '1') {
            View::partial('applications/show', ['app' => $application]);
        } else {
            View::render('applications/show', ['app' => $application]);
        }
        return ['rendered' => true];
    }

    private function approve(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $id = (int) $request->param('id');
        $password = (string) ($body['password'] ?? '');

        if (strlen($password) < 6) {
            return ['redirect' => '/admin/applications', 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak', 'flash_type' => 'error'];
        }

        try {
            $this->repository->approve($id, $password);
        } catch (\PDOException) {
            // Most commonly a UNIQUE constraint violation on users.phone: this applicant's
            // phone number already has an account (e.g. a duplicate application approved twice).
            // NOTE: \PDOException extends \RuntimeException in PHP 8.0+, so this MORE SPECIFIC
            // catch block must come first, or it would be unreachable dead code (shadowed by
            // the \RuntimeException catch below).
            return ['redirect' => '/admin/applications', 'flash' => 'Bu telefon raqami bo\'yicha allaqachon foydalanuvchi mavjud', 'flash_type' => 'error'];
        } catch (\RuntimeException) {
            return ['redirect' => '/admin/applications', 'flash' => 'Ariza allaqachon ko\'rib chiqilgan', 'flash_type' => 'error'];
        }

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_approved')];
    }

    private function reject(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        if (!Csrf::verify($request->formBody()['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $this->repository->reject((int) $request->param('id'));

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_rejected')];
    }
}
