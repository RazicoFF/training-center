<?php
/** @var array $tests */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
    <a href="/admin/tests/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('test_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th>Sarlavha</th><th>Kasb</th><th>Savollar</th><th></th><th></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tests as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['title_uz']) ?></td>
            <td><?= htmlspecialchars($t['profession_name_uz']) ?></td>
            <td><?= (int) $t['question_count'] ?></td>
            <td><a href="/admin/tests/<?= (int) $t['id'] ?>/questions" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('question_add')) ?></a></td>
            <td><a href="/admin/tests/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('test_edit')) ?></a></td>
            <td><a href="/admin/tests/<?= (int) $t['id'] ?>/attempts" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('test_attempts_title')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
