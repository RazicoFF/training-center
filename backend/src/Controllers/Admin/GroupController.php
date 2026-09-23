<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\GroupRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\TeacherRepository;
use App\Services\ScheduleGenerator;
use DateTimeImmutable;

final class GroupController
{
    public function __construct(
        private readonly GroupRepository $groups = new GroupRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly ScheduleGenerator $scheduleGenerator = new ScheduleGenerator()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/groups', fn (Request $req) => $this->index($req));
        // MUST be registered before the /admin/groups/{id} route below.
        $router->get('/admin/groups/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/groups', fn (Request $req) => $this->create($req));
        $router->get('/admin/groups/{id}', fn (Request $req) => $this->show($req));
        $router->post('/admin/groups/{id}/enroll', fn (Request $req) => $this->enroll($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('groups/index', ['groups' => $this->groups->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('groups/create', [
            'professions' => $this->professions->all(),
            'teachers' => $this->teachers->all(),
        ]);
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
        $teacherId = ($body['teacher_id'] ?? '') !== '' ? (int) $body['teacher_id'] : null;
        $name = trim((string) ($body['name'] ?? ''));
        $startDate = (string) ($body['start_date'] ?? '');
        $endDate = (string) ($body['end_date'] ?? '');
        $weekdays = array_map('intval', $body['weekdays'] ?? []);
        $startTime = (string) ($body['start_time'] ?? '09:00');
        $endTime = (string) ($body['end_time'] ?? '11:00');
        $room = (string) ($body['room'] ?? '');

        if ($professionId <= 0 || $name === '' || $startDate === '' || $endDate === '') {
            return ['redirect' => '/admin/groups/create', 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $groupId = $this->groups->create($professionId, $teacherId, $name, $startDate, $endDate);

        $template = array_map(
            static fn (int $weekday) => ['weekday' => $weekday, 'start_time' => $startTime . ':00', 'end_time' => $endTime . ':00', 'room' => $room],
            $weekdays
        );

        $this->scheduleGenerator->generateForGroup($groupId, new DateTimeImmutable($startDate), new DateTimeImmutable($endDate), $template);

        return ['redirect' => "/admin/groups/{$groupId}", 'flash' => Lang::t('group_created')];
    }

    private function show(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $group = $this->groups->find((int) $request->param('id'));

        if ($group === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('groups/show', [
            'group' => $group,
            'students' => $this->groups->studentsIn((int) $group['id']),
            'unassigned' => $this->groups->unassignedApprovedStudents(),
        ]);
        return ['rendered' => true];
    }

    private function enroll(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $groupId = (int) $request->param('id');
        $userId = (int) ($body['user_id'] ?? 0);

        if ($userId > 0) {
            $this->groups->enrollStudent($groupId, $userId);
        }

        return ['redirect' => "/admin/groups/{$groupId}", 'flash' => Lang::t('group_enrolled')];
    }
}
