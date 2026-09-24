<?php
/** @var array $test */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($test['title_uz']) ?></h1>
<form method="post" action="/admin/tests/<?= (int) $test['id'] ?>/edit">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_uz')) ?></label>
        <input type="text" name="title_uz" class="form-control" value="<?= htmlspecialchars($test['title_uz']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_ru')) ?></label>
        <input type="text" name="title_ru" class="form-control" value="<?= htmlspecialchars($test['title_ru']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_passing_score')) ?></label>
        <input type="number" name="passing_score" class="form-control" min="1" max="100" value="<?= (int) $test['passing_score'] ?>" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
