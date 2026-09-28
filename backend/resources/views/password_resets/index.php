<?php
/** @var array $requests */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_password_resets')) ?></h1>
<table class="table table-striped">
    <thead>
        <tr>
            <th>F.I.Sh</th>
            <th><?= htmlspecialchars(Lang::t('teacher_phone')) ?></th>
            <th>Rol</th>
            <th><?= htmlspecialchars(Lang::t('student_status')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['full_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['phone']) ?></td>
            <td><?= htmlspecialchars($r['role'] ?? '-') ?></td>
            <td>
                <?php if ($r['status'] === 'pending'): ?>
                    <span class="badge text-bg-warning"><?= htmlspecialchars(Lang::t('password_reset_pending')) ?></span>
                <?php else: ?>
                    <span class="badge text-bg-success"><?= htmlspecialchars(Lang::t('password_reset_resolved_badge')) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($r['status'] === 'pending'): ?>
                    <form method="post" action="/admin/password-resets/<?= (int) $r['id'] ?>/resolve" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <input type="password" name="password" placeholder="<?= htmlspecialchars(Lang::t('teacher_password')) ?>" required minlength="6" class="form-control form-control-sm" style="width:auto;">
                        <button type="submit" class="btn btn-sm btn-success"><?= htmlspecialchars(Lang::t('password_reset_set_new')) ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
