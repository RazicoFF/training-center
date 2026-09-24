<?php
/** @var array $settings */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_settings')) ?></h1>
<form method="post" action="/admin/settings">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

    <h2 class="h6 mt-3"><?= htmlspecialchars(Lang::t('settings_address_section')) ?></h2>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_address_uz')) ?></label>
            <textarea name="address_uz" class="form-control" rows="2"><?= htmlspecialchars((string) ($settings['address_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_address_ru')) ?></label>
            <textarea name="address_ru" class="form-control" rows="2"><?= htmlspecialchars((string) ($settings['address_ru'] ?? '')) ?></textarea>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('settings_map_embed_url')) ?></label>
        <input type="text" name="map_embed_url" class="form-control" placeholder="https://www.google.com/maps/embed?..." value="<?= htmlspecialchars((string) ($settings['map_embed_url'] ?? '')) ?>">
        <div class="form-text"><?= htmlspecialchars(Lang::t('settings_map_embed_hint')) ?></div>
    </div>

    <h2 class="h6 mt-4"><?= htmlspecialchars(Lang::t('settings_contacts_section')) ?></h2>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_telegram')) ?></label>
            <input type="text" name="telegram" class="form-control" placeholder="@username" value="<?= htmlspecialchars((string) ($settings['telegram'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_email')) ?></label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($settings['email'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_phone')) ?></label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($settings['phone'] ?? '')) ?>">
        </div>
    </div>

    <h2 class="h6 mt-4"><?= htmlspecialchars(Lang::t('settings_about_section')) ?></h2>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_about_uz')) ?></label>
            <textarea name="about_uz" class="form-control" rows="5"><?= htmlspecialchars((string) ($settings['about_uz'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_about_ru')) ?></label>
            <textarea name="about_ru" class="form-control" rows="5"><?= htmlspecialchars((string) ($settings['about_ru'] ?? '')) ?></textarea>
        </div>
    </div>

    <h2 class="h6 mt-4"><?= htmlspecialchars(Lang::t('settings_stats_section')) ?></h2>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_stat_graduates')) ?></label>
            <input type="number" name="stat_graduates" class="form-control" min="0" value="<?= htmlspecialchars((string) ($settings['stat_graduates'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_stat_years')) ?></label>
            <input type="number" name="stat_years" class="form-control" min="0" value="<?= htmlspecialchars((string) ($settings['stat_years'] ?? '')) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('settings_stat_employment')) ?></label>
            <input type="number" name="stat_employment_percent" class="form-control" min="0" max="100" value="<?= htmlspecialchars((string) ($settings['stat_employment_percent'] ?? '')) ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('profession_save')) ?></button>
</form>
