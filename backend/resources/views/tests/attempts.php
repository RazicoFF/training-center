<?php
/** @var array $test */
/** @var array $attempts */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($test['title_uz']) ?> — <?= htmlspecialchars(Lang::t('test_attempts_title')) ?></h1>
<?php if ($attempts === []): ?>
    <p class="text-muted"><?= htmlspecialchars(Lang::t('test_attempts_empty')) ?></p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?= htmlspecialchars(Lang::t('student_name')) ?></th>
                <th><?= htmlspecialchars(Lang::t('student_phone')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_score')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_status')) ?></th>
                <th><?= htmlspecialchars(Lang::t('test_attempt_datetime')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($attempts as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['full_name']) ?></td>
                <td><?= htmlspecialchars($a['phone']) ?></td>
                <td><?= (int) $a['score'] ?>%</td>
                <td>
                    <?php if ((int) $a['passed'] === 1): ?>
                        <span class="badge text-bg-success"><?= htmlspecialchars(Lang::t('test_attempt_passed')) ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-danger"><?= htmlspecialchars(Lang::t('test_attempt_failed')) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a['attempted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
