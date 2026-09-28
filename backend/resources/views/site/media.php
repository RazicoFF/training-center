<?php
/** @var array $mediaItems */
use App\Core\Lang;
use App\Repositories\ProfessionVideoRepository;

$isRu = Lang::current() === 'ru';
?>
<h1 class="h4 mb-3 tc-reveal"><?= htmlspecialchars(Lang::t('nav_media')) ?></h1>
<?php if ($mediaItems === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('media_empty')) ?></p>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($mediaItems as $i => $m): ?>
            <?php $title = $isRu ? ($m['title_ru'] ?? null) : ($m['title_uz'] ?? null); ?>
            <div class="col-md-4">
                <div class="tc-price-card h-100 tc-reveal" style="transition-delay:<?= min($i, 5) * 0.05 ?>s;">
                    <?php if ($m['type'] === 'image'): ?>
                        <button type="button" class="btn p-0 border-0 w-100" data-bs-toggle="modal" data-bs-target="#tcMediaModal<?= $i ?>" style="cursor:zoom-in;">
                            <img src="<?= htmlspecialchars($m['file_url']) ?>" alt="<?= htmlspecialchars((string) $title) ?>">
                        </button>
                    <?php else: ?>
                        <?php $youtubeId = ProfessionVideoRepository::extractYoutubeId($m['youtube_url']); ?>
                        <?php if ($youtubeId !== null): ?>
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>?rel=0&modestbranding=1" title="video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                            </div>
                            <a href="<?= htmlspecialchars($m['youtube_url']) ?>" target="_blank" rel="noopener" class="small d-block px-3 pt-2"><?= htmlspecialchars(Lang::t('site_video_open_youtube')) ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($title)): ?>
                        <div class="p-3 fw-semibold"><?= htmlspecialchars($title) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php // Modals live outside the cards on purpose: a card ancestor with a CSS transform
    // (its :hover state) becomes a "containing block" for any position:fixed descendant,
    // which silently shrinks a Bootstrap modal down to the card's own box instead of the
    // full viewport. Keeping every modal as a direct child of <body> avoids that entirely. ?>
    <?php foreach ($mediaItems as $i => $m): ?>
        <?php if ($m['type'] !== 'image') continue; ?>
        <?php $title = $isRu ? ($m['title_ru'] ?? null) : ($m['title_uz'] ?? null); ?>
        <div class="modal fade" id="tcMediaModal<?= $i ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content tc-media-modal-content">
                    <button type="button" class="btn-close btn-close-white tc-media-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="<?= htmlspecialchars($m['file_url']) ?>" alt="<?= htmlspecialchars((string) $title) ?>" class="tc-media-modal-img">
                    <?php if (!empty($title)): ?>
                        <div class="tc-media-modal-caption"><?= htmlspecialchars($title) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
