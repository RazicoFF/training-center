<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('profession_create')) ?></h1>
<form method="post" action="/admin/professions" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_name_uz')) ?></label>
            <input type="text" name="name_uz" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_name_ru')) ?></label>
            <input type="text" name="name_ru" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_description_uz')) ?></label>
            <textarea name="description_uz" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_description_ru')) ?></label>
            <textarea name="description_ru" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_duration')) ?></label>
            <input type="number" name="duration_days" class="form-control" min="1" value="30" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_price')) ?></label>
            <input type="number" name="price" class="form-control" min="1" step="1000" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_image')) ?></label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_pdf')) ?></label>
        <input type="file" name="pdf" class="form-control" accept=".pdf">
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_career_info_uz')) ?></label>
        <textarea name="career_info_uz" class="form-control" rows="4"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_career_info_ru')) ?></label>
        <textarea name="career_info_ru" class="form-control" rows="4"></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_create')) ?></button>
</form>
