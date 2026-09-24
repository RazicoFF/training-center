<?php
/** @var array $professions */
/** @var int $selectedProfessionId */
/** @var bool $submitted */
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\Lang;

$error = $error ?? null;
$isRu = Lang::current() === 'ru';
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('site_apply_title')) ?></h1>

<?php if ($submitted): ?>
    <div class="alert alert-success tc-alert"><?= htmlspecialchars(Lang::t('site_apply_success')) ?></div>
<?php else: ?>
    <?php if ($error): ?>
        <div class="alert alert-danger tc-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/apply" class="col-md-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_name')) ?></label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('student_phone')) ?></label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
            <select name="profession_id" class="form-select" required>
                <option value=""></option>
                <?php foreach ($professions as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $selectedProfessionId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($isRu ? $p['name_ru'] : $p['name_uz']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('site_apply_submit')) ?></button>
    </form>
<?php endif; ?>
