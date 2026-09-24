<?php
/** @var array $profession */
/** @var array $videos */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($profession['name_uz']) ?></h1>
<?php if (!empty($profession['image_url'])): ?>
    <img src="<?= htmlspecialchars($profession['image_url']) ?>" alt="" style="width:220px;height:140px;object-fit:contain;background:var(--tc-surface-2);" class="mb-3 rounded">
<?php endif; ?>
<?php if (!empty($profession['pdf_url'])): ?>
    <p><a href="<?= htmlspecialchars($profession['pdf_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('profession_pdf_current')) ?></a></p>
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
        <label class="form-label"><?= htmlspecialchars(Lang::t('profession_pdf')) ?></label>
        <input type="file" name="pdf" class="form-control" accept=".pdf">
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

<h2 class="h6 mt-4 mb-3"><?= htmlspecialchars(Lang::t('video_section_title')) ?></h2>
<?php if ($videos !== []): ?>
    <ul class="list-group mb-3">
        <?php foreach ($videos as $v): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="<?= htmlspecialchars($v['youtube_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($v['title_uz'] ?: $v['youtube_url']) ?></a>
                <form method="post" action="/admin/professions/<?= (int) $profession['id'] ?>/videos/<?= (int) $v['id'] ?>/delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('video_delete')) ?></button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<form method="post" action="/admin/professions/<?= (int) $profession['id'] ?>/videos" class="col-md-8">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-2">
        <label class="form-label"><?= htmlspecialchars(Lang::t('video_youtube_url')) ?></label>
        <input type="text" name="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
    </div>
    <div class="row">
        <div class="col mb-2">
            <input type="text" name="title_uz" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_uz')) ?>">
        </div>
        <div class="col mb-2">
            <input type="text" name="title_ru" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('video_title_ru')) ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-outline-primary btn-sm"><?= htmlspecialchars(Lang::t('video_add')) ?></button>
</form>
