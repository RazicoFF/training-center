<?php
/** @var array $group */
/** @var array $students */
/** @var array $unassigned */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars($group['name']) ?></h1>
    <a href="/admin/groups/<?= (int) $group['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
</div>
<p><?= htmlspecialchars($group['profession_name_uz']) ?><?php if (!empty($group['brand_name'])): ?> (<?= htmlspecialchars($group['brand_name']) ?>)<?php endif; ?> — <?= htmlspecialchars($group['teacher_name'] ?? '-') ?></p>

<h2 class="h6">Talabalar</h2>
<ul class="list-group mb-4">
    <?php foreach ($students as $s): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['phone']) ?>)
            <a href="/admin/students/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<h2 class="h6"><?= htmlspecialchars(Lang::t('group_enroll')) ?></h2>
<form method="post" action="/admin/groups/<?= (int) $group['id'] ?>/enroll" class="d-flex gap-2">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <select name="user_id" class="form-select" required>
        <?php foreach ($unassigned as $u): ?>
            <option value="<?= (int) $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['phone']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('group_enroll')) ?></button>
</form>
