<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\UserRepository;

final class AdminUserController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/admins', fn (Request $req) => $this->index($req));
        $router->get('/admin/admins/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/admins', fn (Request $req) => $this->create($req));
        $router->get('/admin/admins/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/admins/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/admins/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('admins/index', [
            'admins' => $this->users->allByRole('admin'),
            'currentAdminId' => (int) ($_SESSION['admin_user_id'] ?? 0),
        ]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('admins/create', []);
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

        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($fullName === '' || $phone === '' || strlen($password) < 6) {
            return ['redirect' => '/admin/admins/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->create($fullName, $phone, Auth::hashPassword($password), 'admin');

        return ['redirect' => '/admin/admins', 'flash' => Lang::t('admin_user_created')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $adminId = (int) $request->param('id');
        $admin = $this->users->find($adminId);

        if ($admin === null || $admin['role'] !== 'admin') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('admins/edit', ['admin' => $admin]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $adminId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $admin = $this->users->find($adminId);
        if ($admin === null || $admin['role'] !== 'admin') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));

        if ($fullName === '' || $phone === '') {
            return ['redirect' => "/admin/admins/{$adminId}/edit", 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->updateProfile($adminId, $fullName, $phone);

        $newPassword = (string) ($body['password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                return ['redirect' => "/admin/admins/{$adminId}/edit", 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak'];
            }
            $this->users->updatePassword($adminId, Auth::hashPassword($newPassword));
        }

        return ['redirect' => '/admin/admins', 'flash' => Lang::t('admin_user_updated')];
    }

    private function delete(Request $request): array
    {
        $claims = AdminAuthMiddleware::requireAdmin();
        if ($claims === null) {
            return ['redirect' => '/admin/login'];
        }

        $adminId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $admin = $this->users->find($adminId);
        if ($admin === null || $admin['role'] !== 'admin') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        if ($adminId === $claims['user_id']) {
            return ['redirect' => '/admin/admins', 'flash' => Lang::t('admin_user_cannot_delete_self')];
        }

        $this->users->delete($adminId);

        return ['redirect' => '/admin/admins', 'flash' => Lang::t('admin_user_deleted')];
    }
}
