<?php
/** @var array $admin */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($admin['full_name']) ?></h1>
<form method="post" action="/admin/admins/<?= (int) $admin['id'] ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_name')) ?></label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_phone')) ?></label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($admin['phone']) ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('student_new_password')) ?></label>
        <input type="password" name="password" class="form-control" minlength="6" placeholder="<?= htmlspecialchars(Lang::t('student_new_password_hint')) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
</form>
