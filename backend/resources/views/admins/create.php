<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('admin_user_create')) ?></h1>
<form method="post" action="/admin/admins">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('student_name')) ?></label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('student_phone')) ?></label>
        <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('student_password')) ?></label>
        <input type="password" name="password" class="form-control" minlength="6" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('admin_user_create')) ?></button>
</form>
