<?php
/** @var array $admins */
/** @var int $currentAdminId */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_admins')) ?></h1>
    <a href="/admin/admins/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('admin_user_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('teacher_name')) ?></th><th><?= htmlspecialchars(Lang::t('teacher_phone')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($admins as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['full_name']) ?><?= (int) $a['id'] === $currentAdminId ? ' <span class="badge text-bg-secondary">' . htmlspecialchars(Lang::t('admin_user_you')) . '</span>' : '' ?></td>
            <td><?= htmlspecialchars($a['phone']) ?></td>
            <td class="text-end">
                <a href="/admin/admins/<?= (int) $a['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
                <?php if ((int) $a['id'] !== $currentAdminId): ?>
                    <form method="post" action="/admin/admins/<?= (int) $a['id'] ?>/delete" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(Lang::t('admin_user_delete_confirm')) ?>');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('admin_user_delete')) ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
