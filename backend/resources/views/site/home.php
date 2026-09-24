<?php
/** @var array $professions */
use App\Core\Lang;

$isRu = Lang::current() === 'ru';
?>
<div class="tc-hero tc-fade-in">
    <h1 class="display-6"><?= htmlspecialchars(Lang::t('site_app_title')) ?></h1>
    <p class="mb-0 fs-5"><?= htmlspecialchars(Lang::t('site_home_tagline')) ?></p>
</div>

<h2 class="h4 mb-3"><?= htmlspecialchars(Lang::t('site_home_professions')) ?></h2>
<div class="row g-4">
    <?php foreach ($professions as $i => $p): ?>
        <div class="col-md-4">
            <div class="tc-price-card tc-fade-in tc-fade-in-<?= min($i + 1, 4) ?>">
                <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name_uz']) ?>">
                <?php endif; ?>
                <div class="p-3">
                    <h3 class="h5"><?= htmlspecialchars($isRu ? $p['name_ru'] : $p['name_uz']) ?></h3>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($isRu ? $p['description_ru'] : $p['description_uz']) ?></p>
                    <p class="tc-price-tag mb-1"><?= number_format((float) $p['price']) ?> UZS</p>
                    <p class="text-muted small mb-3"><?= (int) $p['duration_days'] ?> <?= htmlspecialchars(Lang::t('profession_days')) ?></p>
                    <?php $careerInfo = $isRu ? ($p['career_info_ru'] ?? null) : ($p['career_info_uz'] ?? null); ?>
                    <?php if (!empty($careerInfo)): ?>
                        <p class="small border-top pt-2"><strong><?= htmlspecialchars(Lang::t('site_career_info_label')) ?>:</strong> <?= htmlspecialchars($careerInfo) ?></p>
                    <?php endif; ?>
                    <a href="/apply?profession_id=<?= (int) $p['id'] ?>" class="btn btn-primary w-100"><?= htmlspecialchars(Lang::t('site_nav_apply')) ?></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
