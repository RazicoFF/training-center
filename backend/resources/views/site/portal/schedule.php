<?php
/** @var array $lessons */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_groups')) ?></h1>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('group_start_date')) ?></th><th><?= htmlspecialchars(Lang::t('group_time_start')) ?></th><th><?= htmlspecialchars(Lang::t('group_time_end')) ?></th><th><?= htmlspecialchars(Lang::t('group_room')) ?></th><th><?= htmlspecialchars(Lang::t('group_name')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($lessons as $l): ?>
        <tr>
            <td><?= htmlspecialchars($l['lesson_date']) ?></td>
            <td><?= htmlspecialchars($l['start_time']) ?></td>
            <td><?= htmlspecialchars($l['end_time']) ?></td>
            <td><?= htmlspecialchars($l['room']) ?></td>
            <td><?= htmlspecialchars($l['group_name']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
