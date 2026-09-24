<?php
/** @var array $teacher */
/** @var array|null $profile */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($teacher['full_name']) ?></h1>
<p class="text-muted"><?= htmlspecialchars(Lang::t('teacher_optional_hint')) ?></p>
<?php if (!empty($profile['photo_url'])): ?>
    <img src="<?= htmlspecialchars($profile['photo_url']) ?>" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:12px;" class="mb-3">
<?php endif; ?>
<form method="post" action="/admin/teachers/<?= (int) $teacher['id'] ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_age')) ?></label>
            <input type="number" name="age" class="form-control" min="0" value="<?= htmlspecialchars((string) ($profile['age'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_experience')) ?></label>
            <input type="number" name="experience_years" class="form-control" min="0" value="<?= htmlspecialchars((string) ($profile['experience_years'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_photo')) ?></label>
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text"><?= htmlspecialchars(Lang::t('image_size_hint_teacher')) ?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_skills_uz')) ?></label>
            <textarea name="skills_uz" class="form-control" rows="3"><?= htmlspecialchars((string) ($profile['skills_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_skills_ru')) ?></label>
            <textarea name="skills_ru" class="form-control" rows="3"><?= htmlspecialchars((string) ($profile['skills_ru'] ?? '')) ?></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_education_uz')) ?></label>
            <textarea name="education_uz" class="form-control" rows="3"><?= htmlspecialchars((string) ($profile['education_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_education_ru')) ?></label>
            <textarea name="education_ru" class="form-control" rows="3"><?= htmlspecialchars((string) ($profile['education_ru'] ?? '')) ?></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_telegram')) ?></label>
            <input type="text" name="telegram" class="form-control" placeholder="@username" value="<?= htmlspecialchars((string) ($profile['telegram'] ?? '')) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_email')) ?></label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
</form>
