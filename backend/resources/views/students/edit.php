<?php
/** @var array $student */
/** @var array $enrollments */
/** @var array $testAttempts */
/** @var array $certificates */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($student['full_name']) ?></h1>
<?php if (!empty($student['photo_url'])): ?>
    <img src="<?= htmlspecialchars($student['photo_url']) ?>" alt="" style="width:96px;height:128px;object-fit:cover;border-radius:8px;" class="mb-3">
<?php endif; ?>
<form method="post" action="/admin/students/<?= (int) $student['id'] ?>" class="mb-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_name')) ?></label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_phone')) ?></label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('student_new_password')) ?></label>
        <input type="password" name="password" class="form-control" minlength="6" placeholder="<?= htmlspecialchars(Lang::t('student_new_password_hint')) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
</form>

<h2 class="h6 mb-3"><?= htmlspecialchars(Lang::t('student_enrollments')) ?></h2>
<?php if ($enrollments === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('student_no_enrollments')) ?></p>
<?php else: ?>
    <table class="table table-striped">
        <thead><tr><th><?= htmlspecialchars(Lang::t('group_name')) ?></th><th><?= htmlspecialchars(Lang::t('student_status')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($enrollments as $e): ?>
            <tr>
                <td><a href="/admin/groups/<?= (int) $e['group_id'] ?>"><?= htmlspecialchars($e['group_name']) ?></a></td>
                <td>
                    <form method="post" action="/admin/students/<?= (int) $student['id'] ?>/enrollments/<?= (int) $e['id'] ?>" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <select name="status" class="form-select form-select-sm" style="width:auto;">
                            <option value="active" <?= $e['status'] === 'active' ? 'selected' : '' ?>><?= htmlspecialchars(Lang::t('student_status_active')) ?></option>
                            <option value="completed" <?= $e['status'] === 'completed' ? 'selected' : '' ?>><?= htmlspecialchars(Lang::t('student_status_completed')) ?></option>
                            <option value="dropped" <?= $e['status'] === 'dropped' ? 'selected' : '' ?>><?= htmlspecialchars(Lang::t('student_status_dropped')) ?></option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2 class="h6 mb-3 mt-4"><?= htmlspecialchars(Lang::t('test_attempts_title')) ?></h2>
<?php if ($testAttempts === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('test_attempts_empty')) ?></p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?= htmlspecialchars(Lang::t('nav_tests')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_score')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_status')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_datetime')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($testAttempts as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['title_uz']) ?></td>
                <td><?= (int) $a['score'] ?>%</td>
                <td>
                    <?php if ((int) $a['passed'] === 1): ?>
                        <span class="badge text-bg-success"><?= htmlspecialchars(Lang::t('test_attempt_passed')) ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-danger"><?= htmlspecialchars(Lang::t('test_attempt_failed')) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a['attempted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2 class="h6 mb-3 mt-4"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></h2>
<?php if ($certificates === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('student_no_certificates')) ?></p>
<?php else: ?>
    <table class="table table-striped">
        <thead><tr><th><?= htmlspecialchars(Lang::t('certificate_number')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($certificates as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['certificate_number']) ?> (<?= htmlspecialchars($c['issue_date']) ?>)</td>
                <td class="text-end"><a href="/admin/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
