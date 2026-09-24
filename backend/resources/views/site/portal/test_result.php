<?php
/** @var int $score */
/** @var bool $passed */
use App\Core\Lang;
?>
<div class="alert <?= $passed ? 'alert-success' : 'alert-danger' ?> tc-alert">
    <h1 class="h5"><?= $passed ? htmlspecialchars(Lang::t('site_test_passed')) : htmlspecialchars(Lang::t('site_test_failed')) ?></h1>
    <p class="mb-0"><?= htmlspecialchars(Lang::t('site_test_score')) ?>: <?= (int) $score ?>%</p>
</div>
<a href="/portal/tests" class="btn btn-outline-secondary"><?= htmlspecialchars(Lang::t('nav_tests')) ?></a>
