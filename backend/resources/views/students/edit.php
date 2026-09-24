<?php
/** @var array $student */
/** @var array $enrollments */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($student['full_name']) ?></h1>
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
