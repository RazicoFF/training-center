<?php
/** @var array $certificates */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></h1>
<?php if ($certificates === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('site_portal_no_certificates')) ?></p>
<?php else: ?>
    <table class="table table-striped">
        <thead><tr><th><?= htmlspecialchars(Lang::t('certificate_number')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($certificates as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['certificate_number']) ?> (<?= htmlspecialchars($c['issue_date']) ?>)</td>
                <td class="text-end"><a href="/portal/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
