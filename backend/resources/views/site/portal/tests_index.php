<?php
/** @var array $tests */
use App\Core\Lang;

$isRu = Lang::current() === 'ru';
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
<?php if ($tests === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('site_portal_no_tests')) ?></p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($tests as $t): ?>
            <a href="/portal/tests/<?= (int) $t['id'] ?>" class="list-group-item list-group-item-action">
                <?= htmlspecialchars($isRu ? $t['title_ru'] : $t['title_uz']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
