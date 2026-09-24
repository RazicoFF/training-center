<?php
/** @var array $profession */
/** @var array $videos */
/** @var array $tests */
use App\Core\Lang;
use App\Repositories\ProfessionVideoRepository;

$isRu = Lang::current() === 'ru';
?>
<a href="/" class="d-inline-block mb-3 text-decoration-none">&larr; <?= htmlspecialchars(Lang::t('site_nav_home')) ?></a>
<div class="row g-4">
    <div class="col-md-5">
        <?php if (!empty($profession['image_url'])): ?>
            <img src="<?= htmlspecialchars($profession['image_url']) ?>" alt="" class="w-100 rounded" style="max-height:320px;object-fit:contain;background:var(--tc-surface-2);">
        <?php endif; ?>
    </div>
    <div class="col-md-7">
        <h1 class="h3"><?= htmlspecialchars($isRu ? $profession['name_ru'] : $profession['name_uz']) ?></h1>
        <p><?= htmlspecialchars($isRu ? $profession['description_ru'] : $profession['description_uz']) ?></p>
        <p class="tc-price-tag"><?= number_format((float) $profession['price']) ?> UZS</p>
        <p class="text-muted"><?= (int) $profession['duration_days'] ?> <?= htmlspecialchars(Lang::t('profession_days')) ?></p>

        <?php $careerInfo = $isRu ? ($profession['career_info_ru'] ?? null) : ($profession['career_info_uz'] ?? null); ?>
        <?php if (!empty($careerInfo)): ?>
            <p class="border-top pt-2"><strong><?= htmlspecialchars(Lang::t('site_career_info_label')) ?>:</strong> <?= htmlspecialchars($careerInfo) ?></p>
        <?php endif; ?>

        <?php if (!empty($profession['pdf_url'])): ?>
            <a href="<?= htmlspecialchars($profession['pdf_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary mb-2"><?= htmlspecialchars(Lang::t('site_pdf_open')) ?></a>
        <?php endif; ?>

        <a href="/apply?profession_id=<?= (int) $profession['id'] ?>" class="btn btn-primary d-block mt-2"><?= htmlspecialchars(Lang::t('site_nav_apply')) ?></a>
    </div>
</div>

<?php if ($videos !== []): ?>
    <h2 class="h4 mt-5 mb-3"><?= htmlspecialchars(Lang::t('site_videos_title')) ?></h2>
    <div class="row g-4">
        <?php foreach ($videos as $v): ?>
            <?php $youtubeId = ProfessionVideoRepository::extractYoutubeId($v['youtube_url']); ?>
            <?php if ($youtubeId !== null): ?>
                <div class="col-md-6">
                    <?php $videoTitle = $isRu ? ($v['title_ru'] ?? null) : ($v['title_uz'] ?? null); ?>
                    <?php if (!empty($videoTitle)): ?><p class="fw-semibold"><?= htmlspecialchars($videoTitle) ?></p><?php endif; ?>
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>" title="video" allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($tests !== []): ?>
    <h2 class="h4 mt-5 mb-3"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h2>
    <ul class="list-group mb-4">
        <?php foreach ($tests as $t): ?>
            <li class="list-group-item"><?= htmlspecialchars($isRu ? $t['title_ru'] : $t['title_uz']) ?></li>
        <?php endforeach; ?>
    </ul>
    <p class="text-muted small"><?= htmlspecialchars(Lang::t('site_tests_login_hint')) ?></p>
<?php endif; ?>
