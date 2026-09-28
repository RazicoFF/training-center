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
use App\Repositories\ProfessionRepository;
use App\Repositories\TeacherProfileRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\UploadStore;

final class TeacherController
{
    public function __construct(
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly TeacherProfileRepository $profiles = new TeacherProfileRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly GroupRepository $groups = new GroupRepository(),
        private readonly UploadStore $uploads = new UploadStore()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/teachers', fn (Request $req) => $this->index($req));
        $router->get('/admin/teachers/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/teachers', fn (Request $req) => $this->create($req));
        $router->get('/admin/teachers/{id}', fn (Request $req) => $this->show($req));
        $router->get('/admin/teachers/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/teachers/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/teachers/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $professionId = ($_GET['profession_id'] ?? '') !== '' ? (int) $_GET['profession_id'] : null;

        View::render('teachers/index', [
            'teachers' => $this->teachers->search($q !== '' ? $q : null, $professionId),
            'professions' => $this->professions->all(),
            'q' => $q,
            'professionId' => $professionId,
        ]);
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
            return ['redirect' => '/admin/teachers/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring', 'flash_type' => 'error'];
        }

        $teacherId = $this->users->create($fullName, $phone, Auth::hashPassword($password), 'teacher');
        $this->profiles->upsert($teacherId, $this->buildProfileFields($teacherId, $body, null));

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_created')];
    }

    /**
     * Profile card with everything known about the teacher. With ?modal=1 only the card
     * fragment is returned, for the dialog opened from the teachers list.
     */
    private function show(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $teacherId = (int) $request->param('id');
        $teacher = $this->users->find($teacherId);

        if ($teacher === null || $teacher['role'] !== 'teacher') {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $data = [
            'teacher' => $teacher,
            'profile' => $this->profiles->findByUserId($teacherId),
            'groups' => $this->groups->allForTeacher($teacherId),
        ];
        if (($_GET['modal'] ?? '') === '1') {
            View::partial('teachers/show', $data);
        } else {
            View::render('teachers/show', $data);
        }
        return ['rendered' => true];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
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
        $fields = $this->buildProfileFields($teacherId, $body, $existing);
        $this->profiles->upsert($teacherId, $fields);
        if (($existing['photo_url'] ?? null) !== $fields['photo_url']) {
            $this->uploads->delete($existing['photo_url'] ?? null);
        }

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_updated')];
    }

    private function delete(Request $request): array
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

        $profile = $this->profiles->findByUserId($teacherId);
        $this->groups->unassignTeacherFromGroups($teacherId);
        $this->profiles->deleteByUserId($teacherId);
        $this->uploads->delete($profile['photo_url'] ?? null);
        $this->users->delete($teacherId);

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_deleted')];
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
            'birth_date' => ($body['birth_date'] ?? '') !== '' ? (string) $body['birth_date'] : null,
            'experience_years' => ($body['experience_years'] ?? '') !== '' ? (int) $body['experience_years'] : null,
            'skills_uz' => ($body['skills_uz'] ?? '') !== '' ? trim((string) $body['skills_uz']) : null,
            'skills_ru' => ($body['skills_ru'] ?? '') !== '' ? trim((string) $body['skills_ru']) : null,
            'education_uz' => ($body['education_uz'] ?? '') !== '' ? trim((string) $body['education_uz']) : null,
            'education_ru' => ($body['education_ru'] ?? '') !== '' ? trim((string) $body['education_ru']) : null,
            'telegram' => ($body['telegram'] ?? '') !== '' ? trim((string) $body['telegram']) : null,
            'email' => ($body['email'] ?? '') !== '' ? trim((string) $body['email']) : null,
        ];

        $fields['photo_url'] = $existingProfile['photo_url'] ?? null;

        $photoUrl = $this->uploads->storeUploadedImage('photo', 'teachers', 'teacher-' . $teacherId, UploadStore::PHOTO_MAX_SIDE);
        if ($photoUrl !== null) {
            $fields['photo_url'] = $photoUrl;
        }

        return $fields;
    }
}
