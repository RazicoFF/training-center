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
use App\Repositories\CertificateRepository;
use App\Repositories\GroupRepository;
use App\Repositories\ProfessionBrandRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\StudentStatsRepository;
use App\Repositories\TestRepository;
use App\Repositories\UserRepository;
use App\Services\UploadStore;

final class StudentController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly GroupRepository $groups = new GroupRepository(),
        private readonly StudentStatsRepository $studentStats = new StudentStatsRepository(),
        private readonly TestRepository $tests = new TestRepository(),
        private readonly CertificateRepository $certificates = new CertificateRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly ProfessionBrandRepository $brands = new ProfessionBrandRepository(),
        private readonly UploadStore $uploads = new UploadStore()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/students', fn (Request $req) => $this->index($req));
        $router->get('/admin/students/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/students', fn (Request $req) => $this->create($req));
        $router->get('/admin/students/{id}', fn (Request $req) => $this->show($req));
        $router->get('/admin/students/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/students/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/students/{id}/delete', fn (Request $req) => $this->delete($req));
        $router->post('/admin/students/{id}/enrollments/{enrollmentId}', fn (Request $req) => $this->updateEnrollmentStatus($req));
    }

    private function index(Request $request): array
    {
        $claims = AdminAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/admin/login'];
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $groupId = ($_GET['group_id'] ?? '') !== '' ? (int) $_GET['group_id'] : null;
        $stat = ($_GET['stat'] ?? '') !== '' ? (string) $_GET['stat'] : null;
        $professionId = ($_GET['profession_id'] ?? '') !== '' ? (int) $_GET['profession_id'] : null;
        $brandId = ($_GET['brand_id'] ?? '') !== '' ? (int) $_GET['brand_id'] : null;

        // A teacher only ever sees the students enrolled in a group they teach.
        $teacherId = $claims['role'] === 'teacher' ? $claims['user_id'] : null;
        $groups = $teacherId !== null ? $this->groups->allForTeacher($teacherId) : $this->groups->all();

        View::render('students/index', [
            'students' => $this->studentStats->list($q !== '' ? $q : null, $groupId, $stat, $professionId, $brandId, $teacherId),
            'groups' => $groups,
            'professions' => $this->professions->all(),
            'brands' => $this->brands->all(),
            'q' => $q,
            'groupId' => $groupId,
            'stat' => $stat,
            'professionId' => $professionId,
            'brandId' => $brandId,
        ]);
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
            return ['redirect' => '/admin/students/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring', 'flash_type' => 'error'];
        }

        $studentId = $this->users->create($fullName, $phone, Auth::hashPassword($password), 'student');

        $photoUrl = $this->uploadPhoto($studentId);
        if ($photoUrl !== null) {
            $this->users->updatePhoto($studentId, $photoUrl);
        }

        return ['redirect' => '/admin/students', 'flash' => Lang::t('student_created')];
    }

    private function editForm(Request $request): array
    {
        return $this->renderStudent($request, 'students/edit');
    }

    /**
     * Profile card with the student's details, groups, test results and certificates.
     * With ?modal=1 only the card fragment is returned, for the dialog opened from the list.
     */
    private function show(Request $request): array
    {
        return $this->renderStudent($request, 'students/show', ($_GET['modal'] ?? '') === '1');
    }

    private function renderStudent(Request $request, string $template, bool $partial = false): array
    {
        $claims = AdminAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/admin/login'];
        }

        $studentId = (int) $request->param('id');
        $student = $this->users->find($studentId);

        if ($student === null || $student['role'] !== 'student') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        if ($claims['role'] === 'teacher' && !$this->groups->isStudentTaughtByTeacher($studentId, $claims['user_id'])) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $data = [
            'student' => $student,
            'enrollments' => $this->groups->enrollmentsForUser($studentId),
            'testAttempts' => $this->tests->attemptsForUser($studentId),
            'certificates' => $this->certificates->forUser($studentId),
        ];
        if ($partial) {
            View::partial($template, $data);
        } else {
            View::render($template, $data);
        }
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
            return ['redirect' => "/admin/students/{$studentId}/edit", 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring', 'flash_type' => 'error'];
        }

        $this->users->updateProfile($studentId, $fullName, $phone);

        $photoUrl = $this->uploadPhoto($studentId);
        if ($photoUrl !== null) {
            $this->users->updatePhoto($studentId, $photoUrl);
        }

        $newPassword = (string) ($body['password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                return ['redirect' => "/admin/students/{$studentId}/edit", 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak', 'flash_type' => 'error'];
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

    private function delete(Request $request): array
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

        $this->users->deleteStudentCascade($studentId);

        return ['redirect' => '/admin/students', 'flash' => Lang::t('student_deleted')];
    }

    private function uploadPhoto(int $studentId): ?string
    {
        return $this->uploads->storeUploadedImage('photo', 'students', 'student-' . $studentId, UploadStore::PHOTO_MAX_SIDE);
    }
}
