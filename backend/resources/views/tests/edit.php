<?php
/** @var array $test */
use App\Core\Csrf;
use App\Core\Lang;

if (!function_exists('tc_datetime_local')) {
    function tc_datetime_local(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        return $date === false ? '' : $date->format('Y-m-d\TH:i');
    }
}
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
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_opens_at')) ?></label>
            <input type="datetime-local" name="opens_at" class="form-control" value="<?= htmlspecialchars(tc_datetime_local($test['opens_at'] ?? null)) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_closes_at')) ?></label>
            <input type="datetime-local" name="closes_at" class="form-control" value="<?= htmlspecialchars(tc_datetime_local($test['closes_at'] ?? null)) ?>">
        </div>
    </div>
    <p class="text-muted small"><?= htmlspecialchars(Lang::t('test_schedule_hint')) ?></p>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
