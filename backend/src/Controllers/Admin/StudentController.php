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
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

final class StudentController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly GroupRepository $groups = new GroupRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/students', fn (Request $req) => $this->index($req));
        $router->get('/admin/students/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/students', fn (Request $req) => $this->create($req));
        $router->get('/admin/students/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/students/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/students/{id}/enrollments/{enrollmentId}', fn (Request $req) => $this->updateEnrollmentStatus($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('students/index', ['students' => $this->users->allByRole('student')]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('students/create', []);
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
            return ['redirect' => '/admin/students/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->create($fullName, $phone, Auth::hashPassword($password), 'student');

        return ['redirect' => '/admin/students', 'flash' => Lang::t('student_created')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $studentId = (int) $request->param('id');
        $student = $this->users->find($studentId);

        if ($student === null || $student['role'] !== 'student') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('students/edit', [
            'student' => $student,
            'enrollments' => $this->groups->enrollmentsForUser($studentId),
        ]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $studentId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $student = $this->users->find($studentId);
        if ($student === null || $student['role'] !== 'student') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));

        if ($fullName === '' || $phone === '') {
            return ['redirect' => "/admin/students/{$studentId}/edit", 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->updateProfile($studentId, $fullName, $phone);

        $newPassword = (string) ($body['password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                return ['redirect' => "/admin/students/{$studentId}/edit", 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak'];
            }
            $this->users->updatePassword($studentId, Auth::hashPassword($newPassword));
        }

        return ['redirect' => '/admin/students', 'flash' => Lang::t('student_updated')];
    }

    private function updateEnrollmentStatus(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $studentId = (int) $request->param('id');
        $enrollmentId = (int) $request->param('enrollmentId');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $status = (string) ($body['status'] ?? '');
        $this->groups->updateEnrollmentStatus($enrollmentId, $status);

        return ['redirect' => "/admin/students/{$studentId}/edit", 'flash' => Lang::t('student_updated')];
    }
}
