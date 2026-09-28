<?php
/** @var array $teacher */
/** @var array|null $profile */
/** @var array $groups */
use App\Core\Lang;

$fields = [
    Lang::t('teacher_phone') => $teacher['phone'],
    Lang::t('teacher_birth_date') => $profile['birth_date'] ?? null,
    Lang::t('teacher_age') => $profile['age'] ?? null,
    Lang::t('teacher_experience') => $profile['experience_years'] ?? null,
    Lang::t('teacher_telegram') => $profile['telegram'] ?? null,
    Lang::t('teacher_email') => $profile['email'] ?? null,
    Lang::t('teacher_skills_uz') => $profile['skills_uz'] ?? null,
    Lang::t('teacher_skills_ru') => $profile['skills_ru'] ?? null,
    Lang::t('teacher_education_uz') => $profile['education_uz'] ?? null,
    Lang::t('teacher_education_ru') => $profile['education_ru'] ?? null,
    Lang::t('card_registered_at') => substr((string) $teacher['created_at'], 0, 10),
];
?>
<div class="tc-profile-card">
    <div class="tc-profile-head">
        <?php if (!empty($profile['photo_url'])): ?>
            <img src="<?= htmlspecialchars($profile['photo_url']) ?>" alt="" class="tc-profile-photo">
        <?php else: ?>
            <div class="tc-profile-photo tc-profile-photo-empty"><?= htmlspecialchars(mb_substr($teacher['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars($teacher['full_name']) ?></h2>
            <span class="badge text-bg-secondary"><?= htmlspecialchars(Lang::t('card_role_teacher')) ?></span>
        </div>
    </div>

    <dl class="tc-profile-fields">
        <?php foreach ($fields as $label => $value): ?>
            <?php if ($value !== null && $value !== ''): ?>
                <dt><?= htmlspecialchars($label) ?></dt>
                <dd><?= nl2br(htmlspecialchars((string) $value)) ?></dd>
            <?php endif; ?>
        <?php endforeach; ?>
    </dl>

    <h3 class="h6 mt-3 mb-2"><?= htmlspecialchars(Lang::t('student_enrollments')) ?></h3>
    <?php if ($groups === []): ?>
        <p class="text-muted small"><?= htmlspecialchars(Lang::t('card_no_groups')) ?></p>
    <?php else: ?>
        <ul class="list-group mb-2">
            <?php foreach ($groups as $g): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <a href="/admin/groups/<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></a>
                        <span class="text-muted small d-block"><?= htmlspecialchars($g['profession_name_uz']) ?> · <?= htmlspecialchars($g['start_date']) ?> — <?= htmlspecialchars($g['end_date']) ?></span>
                    </span>
                    <span class="badge text-bg-light"><?= (int) $g['student_count'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3">
        <a href="/admin/teachers/<?= (int) $teacher['id'] ?>/edit" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
    </div>
</div>
