<?php
/** @var int $testId */
/** @var array $questions */
use App\Core\Csrf;
use App\Core\Lang;

$isRu = Lang::current() === 'ru';
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
<form method="post" action="/portal/tests/<?= (int) $testId ?>/submit">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <?php foreach ($questions as $qi => $q): ?>
        <div class="card mb-3">
            <div class="card-body">
                <p class="fw-semibold"><?= ($qi + 1) . '. ' . htmlspecialchars($isRu ? $q['text_ru'] : $q['text_uz']) ?></p>
                <?php foreach ($q['answers'] as $a): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answer_id[<?= (int) $q['id'] ?>]" value="<?= (int) $a['id'] ?>" id="answer-<?= (int) $a['id'] ?>" required>
                        <label class="form-check-label" for="answer-<?= (int) $a['id'] ?>"><?= htmlspecialchars($isRu ? $a['text_ru'] : $a['text_uz']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('site_test_submit')) ?></button>
</form>
