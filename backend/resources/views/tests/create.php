<?php
/** @var array $professions */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('test_create')) ?></h1>
<form method="post" action="/admin/tests" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
        <select name="profession_id" class="form-select" required>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_uz')) ?></label>
        <input type="text" name="title_uz" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_ru')) ?></label>
        <input type="text" name="title_ru" class="form-control" required>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_passing_score')) ?></label>
            <input type="number" name="passing_score" class="form-control" min="1" max="100" value="70" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_random_question_count')) ?></label>
            <input type="number" name="random_question_count" class="form-control" min="1">
            <div class="form-text"><?= htmlspecialchars(Lang::t('test_random_question_count_hint')) ?></div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_time_limit')) ?></label>
            <input type="number" name="time_limit_minutes" class="form-control" min="1">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_opens_at')) ?></label>
            <input type="datetime-local" name="opens_at" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('test_closes_at')) ?></label>
            <input type="datetime-local" name="closes_at" class="form-control">
        </div>
    </div>
    <p class="text-muted small"><?= htmlspecialchars(Lang::t('test_schedule_hint')) ?></p>

    <hr class="my-4">
    <h2 class="h6 mb-2"><?= htmlspecialchars(Lang::t('excel_import_title')) ?></h2>
    <p class="text-muted small"><?= htmlspecialchars(Lang::t('excel_import_hint')) ?></p>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('excel_import_uz_file')) ?></label>
        <input type="file" name="excel_uz" class="form-control" accept=".xlsx,.xls">
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="tc-toggle-ru-excel-create"><?= htmlspecialchars(Lang::t('excel_import_add_ru')) ?></button>
    <div class="mb-3 d-none" id="tc-ru-excel-field-create">
        <label class="form-label"><?= htmlspecialchars(Lang::t('excel_import_ru_file')) ?></label>
        <input type="file" name="excel_ru" class="form-control" accept=".xlsx,.xls">
    </div>

    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('test_create')) ?></button>
</form>
<script>
(function () {
    var toggle = document.getElementById('tc-toggle-ru-excel-create');
    var field = document.getElementById('tc-ru-excel-field-create');
    if (toggle && field) {
        toggle.addEventListener('click', function () {
            field.classList.toggle('d-none');
        });
    }
})();
</script>
