<?php
/** @var array $profession */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($profession['name_uz']) ?></h1>
<form method="post" action="/admin/professions/<?= (int) $profession['id'] ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
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
