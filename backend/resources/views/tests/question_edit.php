<?php
/** @var int $testId */
/** @var array $question */
use App\Core\Csrf;
use App\Core\Lang;

$correctIndex = 0;
foreach ($question['answers'] as $i => $a) {
    if ((int) $a['is_correct'] === 1) {
        $correctIndex = $i;
        break;
    }
}
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('question_edit')) ?></h1>

<form method="post" action="/admin/tests/<?= $testId ?>/questions/<?= (int) $question['id'] ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_uz')) ?></label>
        <input type="text" name="text_uz" class="form-control" value="<?= htmlspecialchars($question['text_uz']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_ru')) ?></label>
        <input type="text" name="text_ru" class="form-control" value="<?= htmlspecialchars($question['text_ru']) ?>" required>
    </div>
    <?php for ($i = 0; $i < 4; $i++): $a = $question['answers'][$i] ?? null; ?>
        <div class="row mb-2 align-items-center">
            <div class="col">
                <input type="text" name="answer_text_uz[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_uz')) ?> <?= $i + 1 ?>" value="<?= htmlspecialchars((string) ($a['text_uz'] ?? '')) ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col">
                <input type="text" name="answer_text_ru[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_ru')) ?> <?= $i + 1 ?>" value="<?= htmlspecialchars((string) ($a['text_ru'] ?? '')) ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col-auto">
                <input type="radio" name="correct_index" value="<?= $i ?>" <?= $i === $correctIndex ? 'checked' : '' ?>> <?= htmlspecialchars(Lang::t('answer_correct')) ?>
            </div>
        </div>
    <?php endfor; ?>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_save')) ?></button>
    <a href="/admin/tests/<?= $testId ?>/questions" class="btn btn-outline-secondary"><?= htmlspecialchars(Lang::t('cancel')) ?></a>
</form>
