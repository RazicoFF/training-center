<?php
/** @var array $user */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('site_portal_welcome')) ?>, <?= htmlspecialchars($user['full_name']) ?></h1>
<div class="row g-3">
    <div class="col-md-4">
        <a href="/portal/schedule" class="card text-decoration-none tc-fade-in tc-fade-in-1">
            <div class="card-body">
                <h2 class="h6"><?= htmlspecialchars(Lang::t('nav_groups')) ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(Lang::t('site_portal_schedule_hint')) ?></p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="/portal/tests" class="card text-decoration-none tc-fade-in tc-fade-in-2">
            <div class="card-body">
                <h2 class="h6"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(Lang::t('site_portal_tests_hint')) ?></p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="/portal/certificates" class="card text-decoration-none tc-fade-in tc-fade-in-3">
            <div class="card-body">
                <h2 class="h6"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(Lang::t('site_portal_certificates_hint')) ?></p>
            </div>
        </a>
    </div>
</div>
