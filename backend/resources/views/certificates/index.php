<?php
/** @var array $certificates */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('certificates_title')) ?></h1>
<table class="table table-striped">
    <thead><tr><th>F.I.Sh</th><th>Kasb</th><th><?= htmlspecialchars(Lang::t('certificate_number')) ?></th><th>Sana</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($certificates as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['student_name']) ?></td>
            <td><?= htmlspecialchars($c['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($c['certificate_number']) ?></td>
            <td><?= htmlspecialchars($c['issue_date']) ?></td>
            <td><a href="/admin/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
