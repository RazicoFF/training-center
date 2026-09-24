<?php
/** @var array $item */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($item['title_uz']) ?></h1>
<?php if (!empty($item['image_url'])): ?>
    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="" style="width:220px;height:140px;object-fit:cover;" class="mb-3 rounded">
<?php endif; ?>
<form method="post" action="/admin/news/<?= (int) $item['id'] ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_title_uz')) ?></label>
            <input type="text" name="title_uz" class="form-control" value="<?= htmlspecialchars($item['title_uz']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_title_ru')) ?></label>
            <input type="text" name="title_ru" class="form-control" value="<?= htmlspecialchars($item['title_ru']) ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_body_uz')) ?></label>
            <textarea name="body_uz" class="form-control" rows="5"><?= htmlspecialchars((string) ($item['body_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_body_ru')) ?></label>
            <textarea name="body_ru" class="form-control" rows="5"><?= htmlspecialchars((string) ($item['body_ru'] ?? '')) ?></textarea>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('news_image')) ?></label>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text"><?= htmlspecialchars(Lang::t('image_size_hint_news')) ?></div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
