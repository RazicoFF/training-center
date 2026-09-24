<?php
/** @var array $teachers */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_teachers')) ?></h1>
    <a href="/admin/teachers/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('teacher_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('teacher_name')) ?></th><th><?= htmlspecialchars(Lang::t('teacher_phone')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($teachers as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['full_name']) ?></td>
            <td><?= htmlspecialchars($t['phone']) ?></td>
            <td class="text-end"><a href="/admin/teachers/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
