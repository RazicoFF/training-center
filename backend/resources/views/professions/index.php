<?php
/** @var array $professions */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_professions')) ?></h1>
<div class="row g-3">
    <?php foreach ($professions as $i => $p): ?>
        <div class="col-md-4">
            <div class="card tc-fade-in tc-fade-in-<?= min($i + 1, 4) ?> h-100">
                <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:100%;height:140px;object-fit:cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="h6"><?= htmlspecialchars($p['name_uz']) ?></h2>
                    <p class="text-muted small mb-2"><?= number_format((float) $p['price']) ?> UZS &middot; <?= (int) $p['duration_days'] ?> <?= htmlspecialchars(Lang::t('profession_days')) ?></p>
                    <a href="/admin/professions/<?= (int) $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('profession_edit')) ?></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
