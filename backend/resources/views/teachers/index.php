<?php
/** @var array $teachers */
/** @var array $professions */
/** @var string $q */
/** @var int|null $professionId */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_teachers')) ?></h1>
    <a href="/admin/teachers/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('teacher_create')) ?></a>
</div>
<form method="get" action="/admin/teachers" class="row g-2 mb-3">
    <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('search_by_name_phone')) ?>" value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="col-md-4">
        <select name="profession_id" class="form-select">
            <option value=""><?= htmlspecialchars(Lang::t('filter_all_professions')) ?></option>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $professionId === (int) $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-100"><?= htmlspecialchars(Lang::t('search_submit')) ?></button>
    </div>
</form>
<table class="table table-striped">
    <thead><tr><th></th><th><?= htmlspecialchars(Lang::t('teacher_name')) ?></th><th><?= htmlspecialchars(Lang::t('teacher_phone')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($teachers as $t): ?>
        <tr>
            <td>
                <?php if (!empty($t['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($t['photo_url']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($t['full_name']) ?></td>
            <td><?= htmlspecialchars($t['phone']) ?></td>
            <td class="text-end"><a href="/admin/teachers/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
