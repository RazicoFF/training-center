<?php
/** @var array $professions */
/** @var array $teachers */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('group_create')) ?></h1>
<form method="post" action="/admin/groups">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_name')) ?></label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
        <div class="row g-2">
            <?php foreach ($professions as $i => $p): ?>
                <div class="col-4">
                    <label class="tc-profession-card d-block mb-0 tc-fade-in tc-fade-in-<?= min($i + 1, 4) ?>">
                        <input type="radio" name="profession_id" value="<?= (int) $p['id'] ?>" class="d-none tc-profession-radio" <?= $i === 0 ? 'checked' : '' ?> required>
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name_uz']) ?>">
                        <?php endif; ?>
                        <div class="tc-profession-name"><?= htmlspecialchars($p['name_uz']) ?></div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_teacher')) ?></label>
        <select name="teacher_id" class="form-select">
            <option value="">-</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_start_date')) ?></label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_end_date')) ?></label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_weekdays')) ?></label>
        <div class="d-flex gap-3 flex-wrap">
            <?php for ($d = 1; $d <= 7; $d++): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="weekdays[]" value="<?= $d ?>" id="wd<?= $d ?>">
                    <label class="form-check-label" for="wd<?= $d ?>"><?= htmlspecialchars(Lang::t('weekday_' . $d)) ?></label>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="row">
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_time_start')) ?></label>
            <input type="time" name="start_time" class="form-control" value="09:00" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_time_end')) ?></label>
            <input type="time" name="end_time" class="form-control" value="11:00" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_room')) ?></label>
            <input type="text" name="room" class="form-control" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('group_create')) ?></button>
</form>
<script>
(function () {
    var cards = document.querySelectorAll('.tc-profession-card');
    function sync() {
        cards.forEach(function (card) {
            var radio = card.querySelector('.tc-profession-radio');
            card.classList.toggle('selected', radio.checked);
        });
    }
    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            card.querySelector('.tc-profession-radio').checked = true;
            sync();
        });
    });
    sync();
})();
</script>
