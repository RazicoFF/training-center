<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('teacher_create')) ?></h1>
<form method="post" action="/admin/teachers" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_name')) ?></label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_phone')) ?></label>
        <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_password')) ?></label>
        <input type="password" name="password" class="form-control" minlength="6" required>
    </div>

    <hr class="my-4">
    <p class="text-muted"><?= htmlspecialchars(Lang::t('teacher_optional_hint')) ?></p>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_age')) ?></label>
            <input type="number" name="age" class="form-control" min="0">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_experience')) ?></label>
            <input type="number" name="experience_years" class="form-control" min="0">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_photo')) ?></label>
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_skills_uz')) ?></label>
            <textarea name="skills_uz" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_skills_ru')) ?></label>
            <textarea name="skills_ru" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_education_uz')) ?></label>
            <textarea name="education_uz" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_education_ru')) ?></label>
            <textarea name="education_ru" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_telegram')) ?></label>
            <input type="text" name="telegram" class="form-control" placeholder="@username">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_email')) ?></label>
            <input type="email" name="email" class="form-control">
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_create')) ?></button>
</form>
