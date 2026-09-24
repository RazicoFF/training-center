<?php
/** @var array $professions */
/** @var array $settings */
use App\Core\Lang;

$isRu = Lang::current() === 'ru';
$aboutText = $isRu ? ($settings['about_ru'] ?? null) : ($settings['about_uz'] ?? null);
$addressText = $isRu ? ($settings['address_ru'] ?? null) : ($settings['address_uz'] ?? null);
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

<?php if (!empty($aboutText)): ?>
    <h2 class="h4 mt-5 mb-3"><?= htmlspecialchars(Lang::t('site_about_title')) ?></h2>
    <p class="tc-fade-in"><?= nl2br(htmlspecialchars($aboutText)) ?></p>
<?php endif; ?>

<?php if (!empty($settings['stat_graduates']) || !empty($settings['stat_years']) || !empty($settings['stat_employment_percent'])): ?>
    <div class="row g-3 my-4 text-center">
        <?php if (!empty($settings['stat_graduates'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-fade-in">
                    <div class="tc-price-tag"><?= (int) $settings['stat_graduates'] ?>+</div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_graduates')) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['stat_years'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-fade-in tc-fade-in-2">
                    <div class="tc-price-tag"><?= (int) $settings['stat_years'] ?></div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_years')) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['stat_employment_percent'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-fade-in tc-fade-in-3">
                    <div class="tc-price-tag"><?= (int) $settings['stat_employment_percent'] ?>%</div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_employment')) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($addressText) || !empty($settings['map_embed_url'])): ?>
    <h2 class="h4 mt-5 mb-3"><?= htmlspecialchars(Lang::t('site_address_title')) ?></h2>
    <div class="row g-4 align-items-start">
        <?php if (!empty($addressText)): ?>
            <div class="col-md-4">
                <p><?= nl2br(htmlspecialchars($addressText)) ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['map_embed_url'])): ?>
            <div class="col-md-8">
                <iframe src="<?= htmlspecialchars($settings['map_embed_url']) ?>" width="100%" height="320" style="border:0;border-radius:12px;" allowfullscreen loading="lazy"></iframe>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($settings['telegram']) || !empty($settings['email']) || !empty($settings['phone'])): ?>
    <h2 class="h4 mt-5 mb-3"><?= htmlspecialchars(Lang::t('site_contacts_title')) ?></h2>
    <ul class="list-unstyled">
        <?php if (!empty($settings['phone'])): ?><li class="mb-1"><?= htmlspecialchars(Lang::t('settings_phone')) ?>: <?= htmlspecialchars($settings['phone']) ?></li><?php endif; ?>
        <?php if (!empty($settings['telegram'])): ?><li class="mb-1">Telegram: <?= htmlspecialchars($settings['telegram']) ?></li><?php endif; ?>
        <?php if (!empty($settings['email'])): ?><li class="mb-1">Email: <?= htmlspecialchars($settings['email']) ?></li><?php endif; ?>
    </ul>
<?php endif; ?>
