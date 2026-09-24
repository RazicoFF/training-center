<?php
/** @var array $teachers */
use App\Core\Lang;

$isRu = Lang::current() === 'ru';

if (!function_exists('tc_initials')) {
    function tc_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = array_map(static fn (string $p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));
        return mb_strtoupper(implode('', $letters));
    }
}
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('site_nav_teachers')) ?></h1>
<div class="row g-4">
    <?php foreach ($teachers as $i => $t): ?>
        <div class="col-md-6 col-lg-4">
            <div class="tc-teacher-card tc-reveal" style="transition-delay:<?= min($i, 5) * 0.06 ?>s;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (!empty($t['photo_url'])): ?>
                        <img src="<?= htmlspecialchars($t['photo_url']) ?>" alt="" class="tc-teacher-photo">
                    <?php else: ?>
                        <div class="tc-teacher-photo-placeholder"><?= htmlspecialchars(tc_initials($t['full_name'])) ?></div>
                    <?php endif; ?>
                    <div>
                        <h2 class="h6 mb-0"><?= htmlspecialchars($t['full_name']) ?></h2>
                        <?php if (!empty($t['experience_years'])): ?>
                            <span class="text-muted small"><?= (int) $t['experience_years'] ?> <?= htmlspecialchars(Lang::t('site_teacher_years')) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $skills = $isRu ? ($t['skills_ru'] ?? null) : ($t['skills_uz'] ?? null); ?>
                <?php if (!empty($skills)): ?>
                    <p class="small mb-1"><strong><?= htmlspecialchars(Lang::t('teacher_skills_uz')) ?>:</strong> <?= htmlspecialchars($skills) ?></p>
                <?php endif; ?>
                <?php $education = $isRu ? ($t['education_ru'] ?? null) : ($t['education_uz'] ?? null); ?>
                <?php if (!empty($education)): ?>
                    <p class="small mb-1"><strong><?= htmlspecialchars(Lang::t('teacher_education_uz')) ?>:</strong> <?= htmlspecialchars($education) ?></p>
                <?php endif; ?>
                <?php if (!empty($t['telegram']) || !empty($t['email'])): ?>
                    <p class="small text-muted mb-0">
                        <?php if (!empty($t['telegram'])): ?><?= htmlspecialchars($t['telegram']) ?><?php endif; ?>
                        <?php if (!empty($t['telegram']) && !empty($t['email'])): ?> &middot; <?php endif; ?>
                        <?php if (!empty($t['email'])): ?><?= htmlspecialchars($t['email']) ?><?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
