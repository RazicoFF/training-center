<?php
/** @var array $students */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_students')) ?></h1>
    <a href="/admin/students/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('student_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('student_name')) ?></th><th><?= htmlspecialchars(Lang::t('student_phone')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($students as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['phone']) ?></td>
            <td class="text-end"><a href="/admin/students/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
