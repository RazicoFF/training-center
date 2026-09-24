<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('news_create')) ?></h1>
<form method="post" action="/admin/news" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_title_uz')) ?></label>
            <input type="text" name="title_uz" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_title_ru')) ?></label>
            <input type="text" name="title_ru" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_body_uz')) ?></label>
            <textarea name="body_uz" class="form-control" rows="5"></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('news_body_ru')) ?></label>
            <textarea name="body_ru" class="form-control" rows="5"></textarea>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('news_image')) ?></label>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('news_create')) ?></button>
</form>
