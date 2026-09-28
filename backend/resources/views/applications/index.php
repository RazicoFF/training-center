<?php
/** @var array $applications */
/** @var string|null $statusFilter */
/** @var array $statusCounts */
/** @var string $q */
use App\Core\Lang;

$tabs = [
    '' => [Lang::t('filter_all_statuses'), array_sum($statusCounts)],
    'pending' => [Lang::t('status_pending'), $statusCounts['pending']],
    'approved' => [Lang::t('status_approved'), $statusCounts['approved']],
    'rejected' => [Lang::t('status_rejected'), $statusCounts['rejected']],
];
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_applications')) ?></h1>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <ul class="nav nav-pills">
        <?php foreach ($tabs as $key => [$label, $count]): ?>
            <?php $query = http_build_query(array_filter(['status' => $key, 'q' => $q])); ?>
            <li class="nav-item">
                <a class="nav-link<?= ($statusFilter ?? '') === $key ? ' active' : '' ?>" href="/admin/applications<?= $query !== '' ? '?' . htmlspecialchars($query) : '' ?>">
                    <?= htmlspecialchars($label) ?>
                    <span class="badge <?= $key === 'pending' && $count > 0 ? 'text-bg-warning' : 'text-bg-secondary' ?>"><?= (int) $count ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <form method="get" action="/admin/applications" class="d-flex gap-2">
        <?php if ($statusFilter !== null): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="<?= htmlspecialchars(Lang::t('search_by_name_phone')) ?>" value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('search_submit')) ?></button>
    </form>
</div>
<?php if ($applications === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('applications_empty')) ?></p>
<?php endif; ?>
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
