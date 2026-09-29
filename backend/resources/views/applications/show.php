<?php
/** @var array $app */
use App\Core\Lang;

$statusBadges = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'];
?>
<div class="tc-profile-card">
    <div class="tc-profile-head">
        <?php if (!empty($app['photo_url'])): ?>
            <a href="<?= htmlspecialchars($app['photo_url']) ?>" target="_blank" rel="noopener">
                <img src="<?= htmlspecialchars($app['photo_url']) ?>" alt="" class="tc-profile-photo">
            </a>
        <?php else: ?>
            <div class="tc-profile-photo tc-profile-photo-empty"><?= htmlspecialchars(mb_substr($app['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars($app['full_name']) ?></h2>
            <span class="badge <?= $statusBadges[$app['status']] ?? 'text-bg-secondary' ?>"><?= htmlspecialchars(Lang::t('status_' . $app['status'])) ?></span>
        </div>
    </div>

    <dl class="tc-profile-fields">
        <dt><?= htmlspecialchars(Lang::t('student_phone')) ?></dt>
        <dd><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $app['phone'])) ?>"><?= htmlspecialchars($app['phone']) ?></a></dd>
        <dt><?= htmlspecialchars(Lang::t('card_profession')) ?></dt>
        <dd><?= htmlspecialchars(Lang::current() === 'ru' ? $app['profession_name_ru'] : $app['profession_name_uz']) ?></dd>
        <?php if (!empty($app['brand_name'])): ?>
            <dt><?= htmlspecialchars(Lang::t('brand_section_title')) ?></dt>
            <dd><?= htmlspecialchars($app['brand_name']) ?></dd>
        <?php endif; ?>
        <dt><?= htmlspecialchars(Lang::t('card_applied_at')) ?></dt>
        <dd><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string) $app['created_at']))) ?></dd>
    </dl>

    <?php if ($app['status'] === 'pending'): ?>
        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
            <?php require __DIR__ . '/_actions.php'; ?>
        </div>
    <?php endif; ?>
</div>
