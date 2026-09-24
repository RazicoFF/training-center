<?php
/** @var array $mediaItems */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_media')) ?></h1>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= htmlspecialchars(Lang::t('media_add_image')) ?></h2>
                <form method="post" action="/admin/media/image" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="mb-2">
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <div class="row">
                        <div class="col mb-2">
                            <input type="text" name="title_uz" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_uz')) ?>">
                        </div>
                        <div class="col mb-2">
                            <input type="text" name="title_ru" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_ru')) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('media_add')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= htmlspecialchars(Lang::t('media_add_video')) ?></h2>
                <form method="post" action="/admin/media/video">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="mb-2">
                        <input type="text" name="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required>
                    </div>
                    <div class="row">
                        <div class="col mb-2">
                            <input type="text" name="title_uz" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_uz')) ?>">
                        </div>
                        <div class="col mb-2">
                            <input type="text" name="title_ru" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_ru')) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('media_add')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($mediaItems as $m): ?>
        <div class="col-md-3">
            <div class="card h-100">
                <?php if ($m['type'] === 'image'): ?>
                    <img src="<?= htmlspecialchars($m['file_url']) ?>" alt="" style="width:100%;height:140px;object-fit:cover;">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-body-secondary" style="height:140px;">
                        <a href="<?= htmlspecialchars($m['youtube_url']) ?>" target="_blank" rel="noopener">YouTube &#9654;</a>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <p class="small mb-2"><?= htmlspecialchars($m['title_uz'] ?: '-') ?></p>
                    <form method="post" action="/admin/media/<?= (int) $m['id'] ?>/delete">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100"><?= htmlspecialchars(Lang::t('video_delete')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
