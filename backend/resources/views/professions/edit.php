<?php
/** @var array $profession */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($profession['name_uz']) ?></h1>
<?php if (!empty($profession['image_url'])): ?>
    <img src="<?= htmlspecialchars($profession['image_url']) ?>" alt="" style="width:220px;height:140px;object-fit:contain;background:var(--tc-surface-2);" class="mb-3 rounded">
<?php endif; ?>
<form method="post" action="/admin/professions/<?= (int) $profession['id'] ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_name_uz')) ?></label>
            <input type="text" name="name_uz" class="form-control" value="<?= htmlspecialchars($profession['name_uz']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_name_ru')) ?></label>
            <input type="text" name="name_ru" class="form-control" value="<?= htmlspecialchars($profession['name_ru']) ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_description_uz')) ?></label>
            <textarea name="description_uz" class="form-control" rows="3"><?= htmlspecialchars((string) ($profession['description_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_description_ru')) ?></label>
            <textarea name="description_ru" class="form-control" rows="3"><?= htmlspecialchars((string) ($profession['description_ru'] ?? '')) ?></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_duration')) ?></label>
            <input type="number" name="duration_days" class="form-control" min="1" value="<?= (int) $profession['duration_days'] ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_price')) ?></label>
            <input type="number" name="price" class="form-control" min="1" step="1000" value="<?= (int) $profession['price'] ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('profession_image')) ?></label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_career_info_uz')) ?></label>
        <textarea name="career_info_uz" class="form-control" rows="4"><?= htmlspecialchars((string) ($profession['career_info_uz'] ?? '')) ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_career_info_ru')) ?></label>
        <textarea name="career_info_ru" class="form-control" rows="4"><?= htmlspecialchars((string) ($profession['career_info_ru'] ?? '')) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
