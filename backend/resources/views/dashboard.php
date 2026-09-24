<?php
/**
 * @var int $pendingApplications
 * @var int $activeGroups
 * @var int $certificatesThisMonth
 * @var array{total:int, studying:int, completed:int, dropped:int, inExam:int} $studentStats
 */
use App\Core\Lang;

$stats = [
    ['label' => Lang::t('dashboard_pending_applications'), 'value' => $pendingApplications, 'icon' => '<path d="M4 5h16l-1.5 9h-4a2.5 2.5 0 0 1-5 0h-4L4 5Z"/><path d="M4 5 2.5 14v4a1 1 0 0 0 1 1h17a1 1 0 0 0 1-1v-4L20 5"/>'],
    ['label' => Lang::t('dashboard_active_groups'), 'value' => $activeGroups, 'icon' => '<circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 8a3 3 0 1 1 0-6"/><path d="M15 13a6 6 0 0 1 6 6"/>'],
    ['label' => Lang::t('dashboard_certificates_month'), 'value' => $certificatesThisMonth, 'icon' => '<circle cx="12" cy="9" r="5"/><path d="m8 13-1.5 7L12 18l5.5 2L16 13"/>'],
];
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_dashboard')) ?></h1>
<div class="row g-3">
    <?php foreach ($stats as $i => $stat): ?>
    <div class="col-md-4">
        <div class="card tc-stat-card tc-fade-in tc-fade-in-<?= $i + 1 ?>">
            <div class="card-body">
                <div class="tc-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px">
                        <?= $stat['icon'] ?>
                    </svg>
                </div>
                <h5 class="card-title text-body-secondary"><?= htmlspecialchars($stat['label']) ?></h5>
                <p class="display-6 mb-0"><?= (int) $stat['value'] ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<h2 class="h5 mt-4 mb-3"><?= htmlspecialchars(Lang::t('dashboard_student_stats')) ?></h2>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
    <?php
    $studentStatCards = [
        ['label' => Lang::t('dashboard_students_total'), 'value' => $studentStats['total']],
        ['label' => Lang::t('dashboard_students_studying'), 'value' => $studentStats['studying']],
        ['label' => Lang::t('dashboard_students_completed'), 'value' => $studentStats['completed']],
        ['label' => Lang::t('dashboard_students_dropped'), 'value' => $studentStats['dropped']],
        ['label' => Lang::t('dashboard_students_in_exam'), 'value' => $studentStats['inExam']],
    ];
    ?>
    <?php foreach ($studentStatCards as $i => $stat): ?>
    <div class="col">
        <div class="card tc-fade-in tc-fade-in-<?= min($i + 1, 4) ?>">
            <div class="card-body">
                <h6 class="card-title text-body-secondary small"><?= htmlspecialchars($stat['label']) ?></h6>
                <p class="h4 mb-0"><?= (int) $stat['value'] ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
