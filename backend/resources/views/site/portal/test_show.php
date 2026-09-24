<?php
/** @var int $testId */
/** @var array $questions */
/** @var int|null $timeLimitMinutes */
use App\Core\Csrf;
use App\Core\Lang;

$isRu = Lang::current() === 'ru';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
    <?php if (!empty($timeLimitMinutes)): ?>
        <span class="badge text-bg-secondary fs-6" id="tc-test-timer" data-seconds-left="<?= (int) $timeLimitMinutes * 60 ?>"></span>
    <?php endif; ?>
</div>
<form method="post" action="/portal/tests/<?= (int) $testId ?>/submit" id="tc-test-form">
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
<?php if (!empty($timeLimitMinutes)): ?>
<script>
(function () {
    var timerEl = document.getElementById('tc-test-timer');
    var form = document.getElementById('tc-test-form');
    var secondsLeft = parseInt(timerEl.getAttribute('data-seconds-left'), 10);

    function render() {
        var m = Math.floor(secondsLeft / 60);
        var s = secondsLeft % 60;
        timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    }
    render();

    var interval = setInterval(function () {
        secondsLeft -= 1;
        if (secondsLeft <= 0) {
            clearInterval(interval);
            render();
            // Radio inputs are required; remove that so an unfinished test can still
            // auto-submit when time runs out instead of being silently blocked.
            form.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); });
            form.submit();
            return;
        }
        render();
    }, 1000);
})();
</script>
<?php endif; ?>
