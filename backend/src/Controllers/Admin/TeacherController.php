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
use App\Repositories\TeacherProfileRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

final class TeacherController
{
    public function __construct(
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly TeacherProfileRepository $profiles = new TeacherProfileRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/teachers', fn (Request $req) => $this->index($req));
        $router->get('/admin/teachers/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/teachers', fn (Request $req) => $this->create($req));
        $router->get('/admin/teachers/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/teachers/{id}', fn (Request $req) => $this->update($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/index', ['teachers' => $this->teachers->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/create', []);
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
            return ['redirect' => '/admin/teachers/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $teacherId = $this->users->create($fullName, $phone, Auth::hashPassword($password), 'teacher');
        $this->profiles->upsert($teacherId, $this->buildProfileFields($teacherId, $body, null));

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_created')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $teacherId = (int) $request->param('id');
        $teacher = $this->users->find($teacherId);

        if ($teacher === null || $teacher['role'] !== 'teacher') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('teachers/edit', [
            'teacher' => $teacher,
            'profile' => $this->profiles->findByUserId($teacherId),
        ]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $teacherId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $teacher = $this->users->find($teacherId);
        if ($teacher === null || $teacher['role'] !== 'teacher') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $existing = $this->profiles->findByUserId($teacherId);
        $this->profiles->upsert($teacherId, $this->buildProfileFields($teacherId, $body, $existing));

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_updated')];
    }

    /**
     * @param array<string,mixed> $body
     * @param array<string,mixed>|null $existingProfile the teacher's current profile row
     *        (for photo_url fallback when this request doesn't upload a new one), or null
     *        for a brand-new teacher who has no profile row yet
     * @return array<string,mixed>
     */
    private function buildProfileFields(int $teacherId, array $body, ?array $existingProfile): array
    {
        $fields = [
            'age' => ($body['age'] ?? '') !== '' ? (int) $body['age'] : null,
            'experience_years' => ($body['experience_years'] ?? '') !== '' ? (int) $body['experience_years'] : null,
            'skills_uz' => ($body['skills_uz'] ?? '') !== '' ? trim((string) $body['skills_uz']) : null,
            'skills_ru' => ($body['skills_ru'] ?? '') !== '' ? trim((string) $body['skills_ru']) : null,
            'education_uz' => ($body['education_uz'] ?? '') !== '' ? trim((string) $body['education_uz']) : null,
            'education_ru' => ($body['education_ru'] ?? '') !== '' ? trim((string) $body['education_ru']) : null,
            'telegram' => ($body['telegram'] ?? '') !== '' ? trim((string) $body['telegram']) : null,
            'email' => ($body['email'] ?? '') !== '' ? trim((string) $body['email']) : null,
        ];

        $fields['photo_url'] = $existingProfile['photo_url'] ?? null;

        $uploadedPhoto = $_FILES['photo'] ?? null;
        if (is_array($uploadedPhoto) && ($uploadedPhoto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/teachers';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $extension = strtolower((string) pathinfo((string) $uploadedPhoto['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($extension, $allowedExtensions, true)) {
                $filename = 'teacher-' . $teacherId . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
                if (move_uploaded_file((string) $uploadedPhoto['tmp_name'], $uploadDir . '/' . $filename)) {
                    $fields['photo_url'] = '/uploads/teachers/' . $filename;
                }
            }
        }

        return $fields;
    }
}
