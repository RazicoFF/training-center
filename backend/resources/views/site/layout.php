<?php
/** @var string $content */
use App\Core\Csrf;
use App\Core\Lang;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$isLoggedIn = isset($_SESSION['site_user_id']);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::current()) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(Lang::t('site_app_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/admin.css" rel="stylesheet">
    <link href="/css/site.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg tc-site-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/images/brand/logo.png" alt="" class="tc-brand-logo">
            <span><?= htmlspecialchars(Lang::t('site_app_title')) ?></span>
        </a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="/" class="nav-link d-inline <?= $currentPath === '/' ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_home')) ?></a>
            <a href="/teachers" class="nav-link d-inline <?= str_starts_with($currentPath, '/teachers') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_teachers')) ?></a>
            <a href="/apply" class="nav-link d-inline <?= str_starts_with($currentPath, '/apply') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_apply')) ?></a>
            <?php if ($isLoggedIn): ?>
                <a href="/portal" class="nav-link d-inline <?= str_starts_with($currentPath, '/portal') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_portal')) ?></a>
                <form method="post" action="/logout" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('logout')) ?></button>
                </form>
            <?php else: ?>
                <a href="/login" class="nav-link d-inline <?= str_starts_with($currentPath, '/login') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_login')) ?></a>
            <?php endif; ?>
            <form method="post" action="/lang" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($currentPath) ?>">
                <input type="hidden" name="locale" value="<?= Lang::current() === 'ru' ? 'uz' : 'ru' ?>">
                <button type="submit" class="tc-theme-toggle" title="<?= htmlspecialchars(Lang::t('lang_uz')) ?> / <?= htmlspecialchars(Lang::t('lang_ru')) ?>" style="font-weight:700;font-size:0.8rem;">
                    <?= Lang::current() === 'ru' ? 'UZ' : 'RU' ?>
                </button>
            </form>
            <button type="button" class="tc-theme-toggle" id="tc-site-theme-toggle" title="Theme">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" id="tc-site-theme-icon"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6 19 19M5 19l1.4-1.4M17.6 6.4 19 5"/></svg>
            </button>
        </div>
    </div>
</nav>
<div class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-info tc-alert"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
<script>
(function () {
    var root = document.documentElement;
    var icon = document.getElementById('tc-site-theme-icon');
    var moonPath = '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/>';
    var sunPath = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6 19 19M5 19l1.4-1.4M17.6 6.4 19 5"/>';
    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
        if (icon) { icon.innerHTML = theme === 'dark' ? moonPath : sunPath; }
    }
    var saved = 'dark';
    try { saved = localStorage.getItem('tc-site-theme') || 'dark'; } catch (e) {}
    applyTheme(saved);
    var toggle = document.getElementById('tc-site-theme-toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            try { localStorage.setItem('tc-site-theme', next); } catch (e) {}
        });
    }
})();
</script>
</body>
</html>
