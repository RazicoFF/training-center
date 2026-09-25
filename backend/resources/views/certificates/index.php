<?php
/** @var array $certificates */
/** @var array $professions */
/** @var array $years */
/** @var string $q */
/** @var int|null $professionId */
/** @var int|null $year */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('certificates_title')) ?></h1>
<form method="get" action="/admin/certificates" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('search_by_name_or_certificate')) ?>" value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="col-md-3">
        <select name="profession_id" class="form-select">
            <option value=""><?= htmlspecialchars(Lang::t('filter_all_professions')) ?></option>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $professionId === (int) $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="year" class="form-select">
            <option value=""><?= htmlspecialchars(Lang::t('filter_all_years')) ?></option>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int) $y ?>" <?= $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-100"><?= htmlspecialchars(Lang::t('search_submit')) ?></button>
    </div>
</form>
<table class="table table-striped">
    <thead><tr><th>F.I.Sh</th><th>Kasb</th><th><?= htmlspecialchars(Lang::t('certificate_number')) ?></th><th>Sana</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($certificates as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['student_name']) ?></td>
            <td><?= htmlspecialchars($c['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($c['certificate_number']) ?></td>
            <td><?= htmlspecialchars($c['issue_date']) ?></td>
            <td class="text-end">
                <a href="/admin/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a>
                <form method="post" action="/admin/certificates/<?= (int) $c['id'] ?>/delete" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(Lang::t('certificate_delete_confirm')) ?>');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('certificate_delete')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
