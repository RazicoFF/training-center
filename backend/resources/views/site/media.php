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
            <div class="col-md-4">
                <div class="tc-price-card h-100 tc-reveal" style="transition-delay:<?= min($i, 5) * 0.05 ?>s;">
                    <?php if ($m['type'] === 'image'): ?>
                        <a href="<?= htmlspecialchars($m['file_url']) ?>" target="_blank" rel="noopener">
                            <img src="<?= htmlspecialchars($m['file_url']) ?>" alt="" style="width:100%;height:220px;object-fit:cover;">
                        </a>
                    <?php else: ?>
                        <?php $youtubeId = ProfessionVideoRepository::extractYoutubeId($m['youtube_url']); ?>
                        <?php if ($youtubeId !== null): ?>
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>" title="video" allowfullscreen loading="lazy"></iframe>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php $title = $isRu ? ($m['title_ru'] ?? null) : ($m['title_uz'] ?? null); ?>
                    <?php if (!empty($title)): ?>
                        <div class="p-3"><?= htmlspecialchars($title) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
