<?php
/** @var array $tests */
use App\Core\Lang;
use App\Repositories\TestRepository;

$isRu = Lang::current() === 'ru';
$testRepository = new TestRepository();

if (!function_exists('tc_format_dt')) {
    function tc_format_dt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        return $date === false ? '' : $date->format('d.m.Y H:i');
    }
}
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
<?php if ($tests === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('site_portal_no_tests')) ?></p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($tests as $t): ?>
            <?php $isOpen = $testRepository->isOpenNow($t); ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div><?= htmlspecialchars($isRu ? $t['title_ru'] : $t['title_uz']) ?></div>
                    <?php if (!empty($t['opens_at']) || !empty($t['closes_at'])): ?>
                        <small class="text-muted">
                            <?php if (!empty($t['opens_at'])): ?>
                                <?= htmlspecialchars(Lang::t('site_test_opens')) ?>: <?= htmlspecialchars(tc_format_dt($t['opens_at'])) ?>
                            <?php endif; ?>
                            <?php if (!empty($t['closes_at'])): ?>
                                &middot; <?= htmlspecialchars(Lang::t('site_test_closes')) ?>: <?= htmlspecialchars(tc_format_dt($t['closes_at'])) ?>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
                <?php if ($isOpen): ?>
                    <a href="/portal/tests/<?= (int) $t['id'] ?>" class="btn btn-sm btn-primary"><?= htmlspecialchars(Lang::t('site_test_start')) ?></a>
                <?php else: ?>
                    <span class="badge text-bg-secondary"><?= htmlspecialchars(Lang::t('site_test_closed')) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
