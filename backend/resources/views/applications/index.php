<?php
/** @var array $applications */
/** @var string|null $statusFilter */
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
            <td><a href="/admin/applications/<?= (int) $app['id'] ?>" data-tc-card class="fw-semibold"><?= htmlspecialchars($app['full_name']) ?></a></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($app['brand_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars(Lang::t('status_' . $app['status'])) ?></td>
            <td>
                <?php require __DIR__ . '/_actions.php'; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
