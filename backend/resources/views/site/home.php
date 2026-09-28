<?php
/** @var array $professions */
/** @var array $settings */
/** @var array $newsItems */
use App\Core\Lang;
use App\Services\UploadStore;

$isRu = Lang::current() === 'ru';
$aboutText = $isRu ? ($settings['about_ru'] ?? null) : ($settings['about_uz'] ?? null);
$addressText = $isRu ? ($settings['address_ru'] ?? null) : ($settings['address_uz'] ?? null);
?>
<div class="tc-hero" style="background-image:url('/images/professions/excavator.jpg');">
    <div class="tc-hero-overlay">
        <h1 class="display-5"><?= htmlspecialchars(Lang::t('site_app_title')) ?></h1>
        <p class="mb-0 fs-5"><?= htmlspecialchars(Lang::t('site_home_tagline')) ?></p>
    </div>
</div>

<h2 class="h4 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('site_home_professions')) ?></h2>
<div class="row g-4">
    <?php foreach ($professions as $i => $p): ?>
        <div class="col-md-4">
            <div class="tc-price-card tc-reveal" style="position:relative;transition-delay:<?= min($i, 3) * 0.08 ?>s;">
                <?php if (!empty($p['pdf_url'])): ?>
                    <span class="badge text-bg-secondary" style="position:absolute;top:8px;right:8px;z-index:1;">PDF</span>
                <?php endif; ?>
                <a href="/professions/<?= (int) $p['id'] ?>" class="text-decoration-none text-reset">
                    <?php if (!empty($p['image_url'])): ?>
                        <img src="<?= htmlspecialchars(UploadStore::thumbUrl($p['image_url'])) ?>" alt="<?= htmlspecialchars($p['name_uz']) ?>" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="p-3 pb-0">
                        <h3 class="h5"><?= htmlspecialchars($isRu ? $p['name_ru'] : $p['name_uz']) ?></h3>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($isRu ? $p['description_ru'] : $p['description_uz']) ?></p>
                        <p class="tc-price-tag mb-1"><?= htmlspecialchars(Lang::money((float) $p['price'])) ?></p>
                        <p class="text-muted small mb-3"><?= (int) $p['duration_days'] ?> <?= htmlspecialchars(Lang::t('profession_days')) ?></p>
                    </div>
                </a>
                <div class="px-3 pb-3">
                    <a href="/apply?profession_id=<?= (int) $p['id'] ?>" class="btn btn-primary w-100"><?= htmlspecialchars(Lang::t('site_nav_apply')) ?></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($newsItems !== []): ?>
    <h2 class="h4 mt-5 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('site_news_title')) ?></h2>
    <div class="row g-4">
        <?php foreach ($newsItems as $i => $n): ?>
            <div class="col-md-4">
                <div class="tc-price-card tc-card-clickable h-100 tc-reveal" style="transition-delay:<?= min($i, 3) * 0.08 ?>s;"
                     role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#tcNewsModal<?= $i ?>"
                     aria-label="<?= htmlspecialchars($isRu ? $n['title_ru'] : $n['title_uz']) ?>">
                    <?php if (!empty($n['image_url'])): ?>
                        <img src="<?= htmlspecialchars(UploadStore::thumbUrl($n['image_url'])) ?>" alt="" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="p-3">
                        <h3 class="h6"><?= htmlspecialchars($isRu ? $n['title_ru'] : $n['title_uz']) ?></h3>
                        <?php $newsBody = $isRu ? ($n['body_ru'] ?? null) : ($n['body_uz'] ?? null); ?>
                        <?php if (!empty($newsBody)): ?>
                            <p class="text-muted small mb-2"><?= htmlspecialchars(mb_strimwidth($newsBody, 0, 160, '...')) ?></p>
                        <?php endif; ?>
                        <span class="tc-card-more small"><?= htmlspecialchars(Lang::t('site_teacher_more')) ?> &rarr;</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php // Modals sit outside the .tc-reveal cards: a transformed ancestor would trap them. ?>
    <?php foreach ($newsItems as $i => $n): ?>
        <?php
        $newsTitle = $isRu ? $n['title_ru'] : $n['title_uz'];
        $newsBody = $isRu ? ($n['body_ru'] ?? null) : ($n['body_uz'] ?? null);
        ?>
        <div class="modal fade" id="tcNewsModal<?= $i ?>" tabindex="-1" aria-hidden="true" aria-labelledby="tcNewsModalTitle<?= $i ?>">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content tc-teacher-modal">
                    <button type="button" class="btn-close tc-teacher-modal-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(Lang::t('card_close')) ?>"></button>
                    <?php if (!empty($n['image_url'])): ?>
                        <img src="<?= htmlspecialchars($n['image_url']) ?>" alt="" class="tc-news-modal-img" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="modal-body">
                        <h2 class="h5 mb-1 pe-4" id="tcNewsModalTitle<?= $i ?>"><?= htmlspecialchars($newsTitle) ?></h2>
                        <?php if (!empty($n['published_at'])): ?>
                            <div class="text-muted small mb-3"><?= htmlspecialchars(date('d.m.Y', strtotime((string) $n['published_at']))) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($newsBody)): ?>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($newsBody)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($aboutText)): ?>
    <h2 class="h4 mt-5 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('site_about_title')) ?></h2>
    <p class="tc-reveal"><?= nl2br(htmlspecialchars($aboutText)) ?></p>
<?php endif; ?>

<?php if (!empty($settings['stat_graduates']) || !empty($settings['stat_years']) || !empty($settings['stat_employment_percent'])): ?>
    <div class="row g-3 my-4 text-center">
        <?php if (!empty($settings['stat_graduates'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-reveal">
                    <div class="tc-price-tag tc-counter" data-counter-target="<?= (int) $settings['stat_graduates'] ?>" data-counter-suffix="+">0</div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_graduates')) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['stat_years'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-reveal" style="transition-delay:0.08s;">
                    <div class="tc-price-tag tc-counter" data-counter-target="<?= (int) $settings['stat_years'] ?>" data-counter-suffix="">0</div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_years')) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['stat_employment_percent'])): ?>
            <div class="col-md-4">
                <div class="tc-price-card p-4 tc-reveal" style="transition-delay:0.16s;">
                    <div class="tc-price-tag tc-counter" data-counter-target="<?= (int) $settings['stat_employment_percent'] ?>" data-counter-suffix="%">0</div>
                    <div class="text-muted small"><?= htmlspecialchars(Lang::t('site_stat_employment')) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($addressText) || !empty($settings['map_embed_url'])): ?>
    <h2 class="h4 mt-5 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('site_address_title')) ?></h2>
    <div class="row g-4 align-items-start">
        <?php if (!empty($addressText)): ?>
            <div class="col-md-4 tc-reveal">
                <p><?= nl2br(htmlspecialchars($addressText)) ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['map_embed_url'])): ?>
            <div class="col-md-8 tc-reveal" style="transition-delay:0.1s;">
                <iframe src="<?= htmlspecialchars($settings['map_embed_url']) ?>" width="100%" height="320" style="border:0;border-radius:12px;" allowfullscreen loading="lazy"></iframe>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($settings['telegram']) || !empty($settings['email']) || !empty($settings['phone'])): ?>
    <h2 class="h4 mt-5 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('site_contacts_title')) ?></h2>
    <ul class="list-unstyled tc-reveal">
        <?php if (!empty($settings['phone'])): ?><li class="mb-1"><?= htmlspecialchars(Lang::t('settings_phone')) ?>: <?= htmlspecialchars($settings['phone']) ?></li><?php endif; ?>
        <?php if (!empty($settings['telegram'])): ?><li class="mb-1">Telegram: <?= htmlspecialchars($settings['telegram']) ?></li><?php endif; ?>
        <?php if (!empty($settings['email'])): ?><li class="mb-1">Email: <?= htmlspecialchars($settings['email']) ?></li><?php endif; ?>
    </ul>
<?php endif; ?>
