<?php
/** @var array $app */
use App\Core\Csrf;
use App\Core\Lang;
?>
<?php if ($app['status'] === 'pending'): ?>
<form method="post" action="/admin/applications/<?= (int) $app['id'] ?>/approve" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <input type="password" name="password" placeholder="<?= htmlspecialchars(Lang::t('teacher_password')) ?>" required minlength="6" class="form-control form-control-sm d-inline w-auto">
    <button type="submit" class="btn btn-sm btn-success"><?= htmlspecialchars(Lang::t('approve')) ?></button>
</form>
<form method="post" action="/admin/applications/<?= (int) $app['id'] ?>/reject" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <button type="submit" class="btn btn-sm btn-danger"><?= htmlspecialchars(Lang::t('reject')) ?></button>
</form>
<?php endif; ?>
