<?php
/** @var string $content */
use App\Core\Csrf;
use App\Core\Lang;

$flash = $_SESSION['flash'] ?? null;
$flashClass = ['error' => 'alert-danger', 'warning' => 'alert-warning'][$_SESSION['flash_type'] ?? ''] ?? 'alert-info';
unset($_SESSION['flash'], $_SESSION['flash_type']);
$isLoggedIn = isset($_SESSION['site_user_id']);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Per-page title/description/share image, passed by controllers as 'meta'. Railway
// terminates TLS in front of the app, so the scheme comes from X-Forwarded-Proto.
$meta = $meta ?? [];
$siteName = Lang::t('site_app_title');
$metaTitle = !empty($meta['title']) ? $meta['title'] . ' — ' . $siteName : $siteName;
$metaDescription = trim((string) preg_replace('/\s+/u', ' ', (string) ($meta['description'] ?? '')));
$metaDescription = mb_strimwidth($metaDescription !== '' ? $metaDescription : Lang::t('site_meta_description'), 0, 160, '…');
$scheme = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$metaImage = $baseUrl . ($meta['image'] ?? '/images/professions/excavator.jpg');
$canonicalUrl = $baseUrl . $currentPath;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::current()) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:locale" content="<?= Lang::current() === 'ru' ? 'ru_RU' : 'uz_UZ' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="/images/brand/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/admin.css" rel="stylesheet">
    <link href="/css/site.css?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/public/css/site.css') ?>" rel="stylesheet">
</head>
<body>
<div class="tc-bg-fx" aria-hidden="true"></div>
<nav class="navbar navbar-expand-lg tc-site-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/images/brand/logo.png" alt="" class="tc-brand-logo">
            <span><?= htmlspecialchars(Lang::t('site_app_title')) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tcNavCollapse" aria-controls="tcNavCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="tcNavCollapse">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 ms-lg-auto mt-3 mt-lg-0">
            <a href="/" class="nav-link d-inline <?= $currentPath === '/' ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_home')) ?></a>
            <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
            <a href="/teachers" class="nav-link d-inline <?= str_starts_with($currentPath, '/teachers') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_teachers')) ?></a>
            <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
            <a href="/media" class="nav-link d-inline <?= str_starts_with($currentPath, '/media') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('nav_media')) ?></a>
            <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
            <a href="/apply" class="nav-link d-inline <?= str_starts_with($currentPath, '/apply') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_apply')) ?></a>
            <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
            <?php if ($isLoggedIn): ?>
                <a href="/portal" class="nav-link d-inline <?= str_starts_with($currentPath, '/portal') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_portal')) ?></a>
                <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
                <form method="post" action="/logout" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('logout')) ?></button>
                </form>
            <?php else: ?>
                <a href="/login" class="nav-link d-inline <?= str_starts_with($currentPath, '/login') ? 'fw-bold' : '' ?>"><?= htmlspecialchars(Lang::t('site_nav_login')) ?></a>
                <span class="tc-nav-sep text-muted d-none d-lg-inline">|</span>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-2">
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
        </div>
    </div>
</nav>
<div class="container py-4">
    <?php if ($flash): ?>
        <div class="alert <?= $flashClass ?> tc-alert" role="alert"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cards that open a modal are divs with role="button"; give them Enter/Space like a real button.
document.querySelectorAll('.tc-card-clickable[role="button"]').forEach(function (card) {
    card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            card.click();
        }
    });
});
</script>
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
(function () {
    var elements = document.querySelectorAll('.tc-reveal');
    if (!('IntersectionObserver' in window) || elements.length === 0) {
        elements.forEach(function (el) { el.classList.add('tc-revealed'); });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('tc-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    elements.forEach(function (el) { observer.observe(el); });
})();
(function () {
    var counters = document.querySelectorAll('.tc-counter');
    if (counters.length === 0) return;

    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-counter-target'), 10) || 0;
        var suffix = el.getAttribute('data-counter-suffix') || '';
        var duration = 1200;
        var start = null;

        function step(timestamp) {
            if (!start) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            el.textContent = Math.floor(progress * target) + suffix;
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target + suffix;
        }
        requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
        counters.forEach(animateCounter);
        return;
    }
    var counterObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { counterObserver.observe(el); });
})();
</script>
</body>
</html>
