<?php
/** @var array $professions */
/** @var array<int, array<int, array{id:int, name:string}>> $brandsByProfession */
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
            <select name="profession_id" id="tc-apply-profession" class="form-select" required>
                <option value=""></option>
                <?php foreach ($professions as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $selectedProfessionId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($isRu ? $p['name_ru'] : $p['name_uz']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 d-none" id="tc-apply-brand-field">
            <label class="form-label"><?= htmlspecialchars(Lang::t('brand_field_label')) ?></label>
            <select name="brand_id" id="tc-apply-brand" class="form-select"></select>
        </div>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('site_apply_submit')) ?></button>
    </form>
    <script>
    (function () {
        var brandsByProfession = <?= json_encode($brandsByProfession, JSON_UNESCAPED_UNICODE) ?>;
        var professionSelect = document.getElementById('tc-apply-profession');
        var brandField = document.getElementById('tc-apply-brand-field');
        var brandSelect = document.getElementById('tc-apply-brand');

        function updateBrands() {
            var brands = brandsByProfession[professionSelect.value] || [];
            brandSelect.innerHTML = '';
            if (brands.length === 0) {
                brandField.classList.add('d-none');
                return;
            }
            brandField.classList.remove('d-none');
            brands.forEach(function (b) {
                var opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                brandSelect.appendChild(opt);
            });
        }

        professionSelect.addEventListener('change', updateBrands);
        updateBrands();
    })();
    </script>
<?php endif; ?>
