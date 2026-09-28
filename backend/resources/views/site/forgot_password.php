<?php
/** @var bool $submitted */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card tc-fade-in">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <img src="/images/brand/logo.png" alt="" class="tc-brand-logo" style="width:64px;height:64px;">
                </div>
                <h1 class="h4 mb-3 text-center"><?= htmlspecialchars(Lang::t('forgot_password_title')) ?></h1>

                <?php if ($submitted): ?>
                    <div class="alert alert-success tc-alert"><?= htmlspecialchars(Lang::t('forgot_password_submitted')) ?></div>
                    <div class="text-center">
                        <a href="/login" class="btn btn-outline-secondary"><?= htmlspecialchars(Lang::t('site_nav_login')) ?></a>
                    </div>
                <?php else: ?>
                    <p class="text-muted small"><?= htmlspecialchars(Lang::t('forgot_password_hint')) ?></p>
                    <form method="post" action="/forgot-password">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(Lang::t('login_phone')) ?></label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(Lang::t('forgot_password_submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
