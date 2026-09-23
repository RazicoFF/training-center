<?php
/**
 * @var int $pendingApplications
 * @var int $activeGroups
 * @var int $certificatesThisMonth
 */
use App\Core\Lang;
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_pending_applications')) ?></h5>
                <p class="display-6"><?= $pendingApplications ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_active_groups')) ?></h5>
                <p class="display-6"><?= $activeGroups ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_certificates_month')) ?></h5>
                <p class="display-6"><?= $certificatesThisMonth ?></p>
            </div>
        </div>
    </div>
</div>
