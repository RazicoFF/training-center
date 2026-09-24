<?php
/** @var array $students */
/** @var array $groups */
/** @var string $q */
/** @var int|null $groupId */
/** @var string|null $stat */
use App\Core\Lang;

$statLabels = [
    'studying' => Lang::t('dashboard_students_studying'),
    'completed' => Lang::t('dashboard_students_completed'),
    'dropped' => Lang::t('dashboard_students_dropped'),
    'in_exam' => Lang::t('dashboard_students_in_exam'),
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_students')) ?></h1>
    <a href="/admin/students/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('student_create')) ?></a>
</div>

<?php if ($stat !== null && isset($statLabels[$stat])): ?>
    <div class="alert alert-info tc-alert d-flex justify-content-between align-items-center">
        <span><?= htmlspecialchars(Lang::t('student_filter_active')) ?>: <strong><?= htmlspecialchars($statLabels[$stat]) ?></strong></span>
        <a href="/admin/students" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('student_filter_clear')) ?></a>
    </div>
<?php endif; ?>

<form method="get" action="/admin/students" class="row g-2 mb-3">
    <?php if ($stat !== null): ?><input type="hidden" name="stat" value="<?= htmlspecialchars($stat) ?>"><?php endif; ?>
    <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('search_by_name_phone')) ?>" value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="col-md-4">
        <select name="group_id" class="form-select">
            <option value=""><?= htmlspecialchars(Lang::t('filter_all_groups')) ?></option>
            <?php foreach ($groups as $g): ?>
                <option value="<?= (int) $g['id'] ?>" <?= $groupId === (int) $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-100"><?= htmlspecialchars(Lang::t('search_submit')) ?></button>
    </div>
</form>

<table class="table table-striped">
    <thead>
        <tr>
            <th><?= htmlspecialchars(Lang::t('student_name')) ?></th>
            <th><?= htmlspecialchars(Lang::t('student_phone')) ?></th>
            <th><?= htmlspecialchars(Lang::t('student_enrollments')) ?></th>
            <th><?= htmlspecialchars(Lang::t('test_attempts_title')) ?></th>
            <th><?= htmlspecialchars(Lang::t('nav_certificates')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($students as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['phone']) ?></td>
            <td><?= htmlspecialchars($s['group_names'] ?? '') ?: '-' ?></td>
            <td><?= (int) $s['tests_passed'] ?>/<?= (int) $s['tests_taken'] ?></td>
            <td><?= (int) $s['certificate_count'] ?></td>
            <td class="text-end">
                <?php if (!empty($s['latest_certificate_id'])): ?>
                    <a href="/admin/certificates/<?= (int) $s['latest_certificate_id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a>
                <?php endif; ?>
                <a href="/admin/students/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
