<?php
/** @var int $testId */
/** @var array $questions */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('question_add')) ?></h1>

<?php foreach ($questions as $q): ?>
    <div class="card mb-2"><div class="card-body">
        <p class="fw-bold"><?= htmlspecialchars($q['text_uz']) ?></p>
        <ul>
        <?php foreach ($q['answers'] as $a): ?>
            <li><?= htmlspecialchars($a['text_uz']) ?><?= ((int) $a['is_correct'] === 1) ? ' ✓' : '' ?></li>
        <?php endforeach; ?>
        </ul>
    </div></div>
<?php endforeach; ?>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-2"><?= htmlspecialchars(Lang::t('excel_import_title')) ?></h2>
        <p class="text-muted small"><?= htmlspecialchars(Lang::t('excel_import_hint')) ?></p>
        <form method="post" action="/admin/tests/<?= $testId ?>/questions/import" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(Lang::t('excel_import_uz_file')) ?></label>
                <input type="file" name="excel_uz" class="form-control" accept=".xlsx,.xls" required>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="tc-toggle-ru-excel"><?= htmlspecialchars(Lang::t('excel_import_add_ru')) ?></button>
            <div class="mb-3 d-none" id="tc-ru-excel-field">
                <label class="form-label"><?= htmlspecialchars(Lang::t('excel_import_ru_file')) ?></label>
                <input type="file" name="excel_ru" class="form-control" accept=".xlsx,.xls">
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('excel_import_submit')) ?></button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var toggle = document.getElementById('tc-toggle-ru-excel');
    var field = document.getElementById('tc-ru-excel-field');
    if (toggle && field) {
        toggle.addEventListener('click', function () {
            field.classList.toggle('d-none');
        });
    }
})();
</script>

<form method="post" action="/admin/tests/<?= $testId ?>/questions" class="mt-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_uz')) ?></label>
        <input type="text" name="text_uz" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_ru')) ?></label>
        <input type="text" name="text_ru" class="form-control" required>
    </div>
    <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="row mb-2 align-items-center">
            <div class="col">
                <input type="text" name="answer_text_uz[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_uz')) ?> <?= $i + 1 ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col">
                <input type="text" name="answer_text_ru[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_ru')) ?> <?= $i + 1 ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col-auto">
                <input type="radio" name="correct_index" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?>> <?= htmlspecialchars(Lang::t('answer_correct')) ?>
            </div>
        </div>
    <?php endfor; ?>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('question_add')) ?></button>
</form>
