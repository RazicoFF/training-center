<?php
/** @var array $student */
/** @var array $enrollments */
/** @var array $testAttempts */
/** @var array $certificates */
use App\Core\Lang;

$statusLabels = [
    'active' => Lang::t('student_status_active'),
    'completed' => Lang::t('student_status_completed'),
    'dropped' => Lang::t('student_status_dropped'),
];
$passedCount = count(array_filter($testAttempts, static fn (array $a) => (int) $a['passed'] === 1));
?>
<div class="tc-profile-card">
    <div class="tc-profile-head">
        <?php if (!empty($student['photo_url'])): ?>
            <img src="<?= htmlspecialchars($student['photo_url']) ?>" alt="" class="tc-profile-photo">
        <?php else: ?>
            <div class="tc-profile-photo tc-profile-photo-empty"><?= htmlspecialchars(mb_substr($student['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars($student['full_name']) ?></h2>
            <span class="badge text-bg-secondary"><?= htmlspecialchars(Lang::t('card_role_student')) ?></span>
        </div>
    </div>

    <dl class="tc-profile-fields">
        <dt><?= htmlspecialchars(Lang::t('student_phone')) ?></dt>
        <dd><?= htmlspecialchars($student['phone']) ?></dd>
        <dt><?= htmlspecialchars(Lang::t('card_registered_at')) ?></dt>
        <dd><?= htmlspecialchars(substr((string) $student['created_at'], 0, 10)) ?></dd>
        <dt><?= htmlspecialchars(Lang::t('test_attempts_title')) ?></dt>
        <dd><?= $passedCount ?>/<?= count($testAttempts) ?></dd>
        <dt><?= htmlspecialchars(Lang::t('nav_certificates')) ?></dt>
        <dd><?= count($certificates) ?></dd>
    </dl>

    <h3 class="h6 mt-3 mb-2"><?= htmlspecialchars(Lang::t('student_enrollments')) ?></h3>
    <?php if ($enrollments === []): ?>
        <p class="text-muted small"><?= htmlspecialchars(Lang::t('student_no_enrollments')) ?></p>
    <?php else: ?>
        <ul class="list-group mb-2">
            <?php foreach ($enrollments as $e): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="/admin/groups/<?= (int) $e['group_id'] ?>"><?= htmlspecialchars($e['group_name']) ?></a>
                    <span class="badge text-bg-light"><?= htmlspecialchars($statusLabels[$e['status']] ?? $e['status']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($testAttempts !== []): ?>
        <h3 class="h6 mt-3 mb-2"><?= htmlspecialchars(Lang::t('test_attempts_title')) ?></h3>
        <ul class="list-group mb-2">
            <?php foreach ($testAttempts as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <?= htmlspecialchars($a['title_uz']) ?>
                        <span class="text-muted small d-block"><?= htmlspecialchars($a['attempted_at']) ?></span>
                    </span>
                    <span class="badge <?= (int) $a['passed'] === 1 ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (int) $a['score'] ?>%</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($certificates !== []): ?>
        <h3 class="h6 mt-3 mb-2"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></h3>
        <ul class="list-group mb-2">
            <?php foreach ($certificates as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($c['certificate_number']) ?> <span class="text-muted small">(<?= htmlspecialchars($c['issue_date']) ?>)</span></span>
                    <a href="/admin/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3">
        <a href="/admin/students/<?= (int) $student['id'] ?>/edit" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
    </div>
</div>
