<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('account_title')) ?></h1>
<form method="post" action="/admin/account/password" class="col-md-5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('account_current_password')) ?></label>
        <input type="password" name="current_password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('account_new_password')) ?></label>
        <input type="password" name="new_password" class="form-control" minlength="6" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('account_confirm_password')) ?></label>
        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
