<?php
/** @var array $groups */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_groups')) ?></h1>
    <a href="/admin/groups/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('group_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('group_name')) ?></th><th><?= htmlspecialchars(Lang::t('group_profession')) ?></th><th><?= htmlspecialchars(Lang::t('group_teacher')) ?></th><th></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($groups as $g): ?>
        <tr>
            <td><a href="/admin/groups/<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></a></td>
            <td><?= htmlspecialchars($g['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($g['teacher_name'] ?? '-') ?></td>
            <td><?= (int) $g['student_count'] ?></td>
            <td><a href="/admin/groups/<?= (int) $g['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
