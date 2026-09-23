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
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

final class TeacherController
{
    public function __construct(
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/teachers', fn (Request $req) => $this->index($req));
        $router->get('/admin/teachers/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/teachers', fn (Request $req) => $this->create($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/index', ['teachers' => $this->teachers->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/create', []);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
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
            return ['redirect' => '/admin/teachers/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->create($fullName, $phone, Auth::hashPassword($password), 'teacher');

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_created')];
    }
}
