<?php
/** @var array $group */
/** @var array $teachers */
/** @var array $brandsForProfession */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($group['name']) ?></h1>
<form method="post" action="/admin/groups/<?= (int) $group['id'] ?>/edit">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_name')) ?></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($group['name']) ?>" required>
    </div>
    <?php if ($brandsForProfession !== []): ?>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('brand_field_label')) ?></label>
        <select name="brand_id" class="form-select">
            <option value="">-</option>
            <?php foreach ($brandsForProfession as $b): ?>
                <option value="<?= (int) $b['id'] ?>" <?= (int) ($group['brand_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_teacher')) ?></label>
        <select name="teacher_id" class="form-select">
            <option value="">-</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= (int) ($group['teacher_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_start_date')) ?></label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($group['start_date']) ?>" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_end_date')) ?></label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($group['end_date']) ?>" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
