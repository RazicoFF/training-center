<?php
/** @var array $applications */
/** @var string|null $statusFilter */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_applications')) ?></h1>
<table class="table table-striped">
    <thead>
        <tr><th></th><th>F.I.Sh</th><th>Telefon</th><th>Kasb</th><th><?= htmlspecialchars(Lang::t('brand_section_title')) ?></th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($applications as $app): ?>
        <tr>
            <td>
                <?php if (!empty($app['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($app['photo_url']) ?>" alt="" style="width:36px;height:48px;object-fit:cover;border-radius:4px;">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($app['brand_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars(Lang::t('status_' . $app['status'])) ?></td>
            <td>
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
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
