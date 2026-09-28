<?php
/** @var array $item */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('media_edit_title')) ?></h1>
<div class="card col-lg-8">
    <div class="card-body">
        <form method="post" action="/admin/media/<?= (int) $item['id'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <?php if ($item['type'] === 'image'): ?>
                <img src="<?= htmlspecialchars((string) $item['file_url']) ?>" alt="" class="rounded mb-3 d-block" style="width:100%;max-width:360px;aspect-ratio:3/2;object-fit:cover;">
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(Lang::t('media_replace_image')) ?></label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text"><?= htmlspecialchars(Lang::t('image_size_hint_media')) ?></div>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(Lang::t('video_youtube_url')) ?></label>
                    <input type="text" name="youtube_url" class="form-control" value="<?= htmlspecialchars((string) $item['youtube_url']) ?>" required>
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= htmlspecialchars(Lang::t('video_title_uz')) ?></label>
                    <input type="text" name="title_uz" class="form-control" value="<?= htmlspecialchars((string) ($item['title_uz'] ?? '')) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= htmlspecialchars(Lang::t('video_title_ru')) ?></label>
                    <input type="text" name="title_ru" class="form-control" value="<?= htmlspecialchars((string) ($item['title_ru'] ?? '')) ?>">
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label"><?= htmlspecialchars(Lang::t('media_sort_order')) ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int) $item['sort_order'] ?>">
                <div class="form-text"><?= htmlspecialchars(Lang::t('media_sort_order_hint')) ?></div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
                <a href="/admin/media" class="btn btn-outline-secondary"><?= htmlspecialchars(Lang::t('card_back')) ?></a>
            </div>
        </form>
    </div>
</div>
