<?php
/** @var array $professions */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('test_create')) ?></h1>
<form method="post" action="/admin/tests">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
        <select name="profession_id" class="form-select" required>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_uz')) ?></label>
        <input type="text" name="title_uz" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_ru')) ?></label>
        <input type="text" name="title_ru" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_passing_score')) ?></label>
        <input type="number" name="passing_score" class="form-control" min="1" max="100" value="70" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_opens_at')) ?></label>
            <input type="datetime-local" name="opens_at" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_closes_at')) ?></label>
            <input type="datetime-local" name="closes_at" class="form-control">
        </div>
    </div>
    <p class="text-muted small"><?= htmlspecialchars(Lang::t('test_schedule_hint')) ?></p>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('test_create')) ?></button>
</form>
