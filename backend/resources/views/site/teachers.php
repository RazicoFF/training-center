<?php
/** @var array $teachers */
use App\Core\Lang;
use App\Services\UploadStore;

$isRu = Lang::current() === 'ru';

if (!function_exists('tc_initials')) {
    function tc_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = array_map(static fn (string $p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));
        return mb_strtoupper(implode('', $letters));
    }
}

if (!function_exists('tc_teacher_age')) {
    /** The stored age, or one derived from birth_date when only that was filled in. */
    function tc_teacher_age(array $t): ?int
    {
        if (!empty($t['age'])) {
            return (int) $t['age'];
        }
        if (!empty($t['birth_date'])) {
            return (new DateTimeImmutable((string) $t['birth_date']))->diff(new DateTimeImmutable('today'))->y;
        }
        return null;
    }
}
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('site_nav_teachers')) ?></h1>
<div class="row g-4">
    <?php foreach ($teachers as $i => $t): ?>
        <?php
        $skills = $isRu ? ($t['skills_ru'] ?? null) : ($t['skills_uz'] ?? null);
        $education = $isRu ? ($t['education_ru'] ?? null) : ($t['education_uz'] ?? null);
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="tc-teacher-card tc-card-clickable tc-reveal" style="transition-delay:<?= min($i, 5) * 0.06 ?>s;"
                 role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#tcTeacherModal<?= $i ?>"
                 aria-label="<?= htmlspecialchars($t['full_name']) ?>">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (!empty($t['photo_url'])): ?>
                        <img src="<?= htmlspecialchars(UploadStore::thumbUrl($t['photo_url'])) ?>" alt="" class="tc-teacher-photo" loading="lazy" decoding="async">
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
                <?php if (!empty($skills)): ?>
                    <p class="small mb-1 tc-clamp-2"><strong><?= htmlspecialchars(Lang::t('site_teacher_skills')) ?>:</strong> <?= htmlspecialchars($skills) ?></p>
                <?php endif; ?>
                <?php if (!empty($education)): ?>
                    <p class="small mb-1 tc-clamp-2"><strong><?= htmlspecialchars(Lang::t('site_teacher_education')) ?>:</strong> <?= htmlspecialchars($education) ?></p>
                <?php endif; ?>
                <span class="tc-card-more small"><?= htmlspecialchars(Lang::t('site_teacher_more')) ?> &rarr;</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php // Modals sit outside the .tc-reveal cards (see site/media.php): a transformed
// ancestor would trap the fixed-position modal inside the card's box. ?>
<?php foreach ($teachers as $i => $t): ?>
    <?php
    $skills = $isRu ? ($t['skills_ru'] ?? null) : ($t['skills_uz'] ?? null);
    $education = $isRu ? ($t['education_ru'] ?? null) : ($t['education_uz'] ?? null);
    $professionNames = $isRu ? ($t['professions_ru'] ?? null) : ($t['professions_uz'] ?? null);
    $age = tc_teacher_age($t);
    $telegram = trim((string) ($t['telegram'] ?? ''));
    $telegramHandle = ltrim(preg_replace('#^https?://t\.me/#i', '', $telegram), '@');
    ?>
    <div class="modal fade" id="tcTeacherModal<?= $i ?>" tabindex="-1" aria-hidden="true" aria-labelledby="tcTeacherModalTitle<?= $i ?>">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content tc-teacher-modal">
                <button type="button" class="btn-close tc-teacher-modal-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(Lang::t('card_close')) ?>"></button>
                <div class="modal-body">
                    <div class="tc-teacher-modal-head">
                        <?php if (!empty($t['photo_url'])): ?>
                            <img src="<?= htmlspecialchars($t['photo_url']) ?>" alt="" class="tc-teacher-modal-photo" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="tc-teacher-modal-photo tc-teacher-photo-placeholder"><?= htmlspecialchars(tc_initials($t['full_name'])) ?></div>
                        <?php endif; ?>
                        <div>
                            <h2 class="h5 mb-1" id="tcTeacherModalTitle<?= $i ?>"><?= htmlspecialchars($t['full_name']) ?></h2>
                            <?php if (!empty($professionNames)): ?>
                                <div class="text-muted small"><?= htmlspecialchars($professionNames) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <dl class="tc-teacher-modal-fields">
                        <?php if (!empty($t['experience_years'])): ?>
                            <dt><?= htmlspecialchars(Lang::t('teacher_experience')) ?></dt>
                            <dd><?= (int) $t['experience_years'] ?></dd>
                        <?php endif; ?>
                        <?php if ($age !== null): ?>
                            <dt><?= htmlspecialchars(Lang::t('teacher_age')) ?></dt>
                            <dd><?= $age ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($skills)): ?>
                            <dt><?= htmlspecialchars(Lang::t('site_teacher_skills')) ?></dt>
                            <dd><?= nl2br(htmlspecialchars($skills)) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($education)): ?>
                            <dt><?= htmlspecialchars(Lang::t('site_teacher_education')) ?></dt>
                            <dd><?= nl2br(htmlspecialchars($education)) ?></dd>
                        <?php endif; ?>
                        <?php if ($telegramHandle !== ''): ?>
                            <dt><?= htmlspecialchars(Lang::t('teacher_telegram')) ?></dt>
                            <dd><a href="https://t.me/<?= htmlspecialchars(rawurlencode($telegramHandle)) ?>" target="_blank" rel="noopener">@<?= htmlspecialchars($telegramHandle) ?></a></dd>
                        <?php endif; ?>
                        <?php if (!empty($t['email'])): ?>
                            <dt><?= htmlspecialchars(Lang::t('teacher_email')) ?></dt>
                            <dd><a href="mailto:<?= htmlspecialchars($t['email']) ?>"><?= htmlspecialchars($t['email']) ?></a></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

