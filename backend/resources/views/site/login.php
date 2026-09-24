<?php
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card tc-fade-in">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center"><?= htmlspecialchars(Lang::t('site_nav_portal')) ?></h1>
                <?php if ($error !== null): ?>
                    <div class="alert alert-danger tc-alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="/login">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(Lang::t('login_phone')) ?></label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(Lang::t('login_password')) ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(Lang::t('login_submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
