<?php
/** @var string $content */
use App\Core\Csrf;
use App\Core\Lang;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$isLoggedIn = isset($_SESSION['admin_user_id']);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$navItems = [
    ['href' => '/admin', 'label' => Lang::t('nav_dashboard'), 'match' => '/admin', 'icon' => 'home'],
    ['href' => '/admin/applications', 'label' => Lang::t('nav_applications'), 'match' => '/admin/applications', 'icon' => 'inbox'],
    ['href' => '/admin/groups', 'label' => Lang::t('nav_groups'), 'match' => '/admin/groups', 'icon' => 'users'],
    ['href' => '/admin/students', 'label' => Lang::t('nav_students'), 'match' => '/admin/students', 'icon' => 'graduation'],
    ['href' => '/admin/teachers', 'label' => Lang::t('nav_teachers'), 'match' => '/admin/teachers', 'icon' => 'user-check'],
    ['href' => '/admin/professions', 'label' => Lang::t('nav_professions'), 'match' => '/admin/professions', 'icon' => 'briefcase'],
    ['href' => '/admin/tests', 'label' => Lang::t('nav_tests'), 'match' => '/admin/tests', 'icon' => 'clipboard'],
    ['href' => '/admin/certificates', 'label' => Lang::t('nav_certificates'), 'match' => '/admin/certificates', 'icon' => 'award'],
];

$icons = [
    'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9a1 1 0 0 0 1 1h4v-5h2v5h4a1 1 0 0 0 1-1v-9"/>',
    'inbox' => '<path d="M4 5h16l-1.5 9h-4a2.5 2.5 0 0 1-5 0h-4L4 5Z"/><path d="M4 5 2.5 14v4a1 1 0 0 0 1 1h17a1 1 0 0 0 1-1v-4L20 5"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 8a3 3 0 1 1 0-6"/><path d="M15 13a6 6 0 0 1 6 6"/>',
    'user-check' => '<circle cx="10" cy="8" r="3"/><path d="M4 19a6 6 0 0 1 12 0"/><path d="m16 11 2 2 4-4"/>',
    'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M9 11h6"/><path d="M9 15h6"/>',
    'award' => '<circle cx="12" cy="9" r="5"/><path d="m8 13-1.5 7L12 18l5.5 2L16 13"/>',
    'graduation' => '<path d="M2 9 12 4l10 5-10 5-10-5Z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/>',
    'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6 19 19M5 19l1.4-1.4M17.6 6.4 19 5"/>',
    'moon' => '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/>',
];

if (!function_exists('tc_icon')) {
    function tc_icon(array $icons, string $name): string
    {
        $body = $icons[$name] ?? '';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::current()) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(Lang::t('app_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/admin.css" rel="stylesheet">
</head>
<body>
<?php if ($isLoggedIn): ?>
<div class="tc-shell">
    <aside class="tc-sidebar">
        <a class="tc-brand" href="/admin">
            <span class="tc-brand-mark"><?= Lang::current() === 'ru' ? 'УЦ' : 'OM' ?></span>
            <span><?= htmlspecialchars(Lang::t('app_title')) ?></span>
        </a>
        <nav class="tc-nav">
            <?php foreach ($navItems as $item):
                $isActive = $item['match'] === '/admin' ? $currentPath === '/admin' : str_starts_with($currentPath, $item['match']);
            ?>
                <a class="tc-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
                    <span class="tc-nav-icon"><?= tc_icon($icons, $item['icon']) ?></span>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="tc-sidebar-footer">
            <form method="post" action="/admin/lang">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/admin') ?>">
                <input type="hidden" name="locale" value="<?= Lang::current() === 'uz' ? 'ru' : 'uz' ?>">
                <button type="submit" class="tc-icon-btn" title="<?= Lang::current() === 'uz' ? htmlspecialchars(Lang::t('lang_ru')) : htmlspecialchars(Lang::t('lang_uz')) ?>">
                    <?= strtoupper(Lang::current()) ?>
                </button>
            </form>
            <button type="button" id="theme-toggle" class="tc-theme-toggle" title="<?= htmlspecialchars(Lang::t('theme_toggle')) ?>">
                <span id="theme-toggle-icon"><?= tc_icon($icons, 'sun') ?></span>
            </button>
            <form method="post" action="/admin/logout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <button type="submit" class="tc-icon-btn text-danger" title="<?= htmlspecialchars(Lang::t('logout')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>
    <main class="tc-main">
        <?php if ($flash !== null): ?>
            <div class="alert alert-success tc-alert"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <div class="tc-fade-in">
            <?= $content ?>
        </div>
    </main>
</div>
<?php else: ?>
<div class="tc-topbar" style="padding: 1rem 1.5rem 0;">
    <button type="button" id="theme-toggle" class="tc-theme-toggle" title="<?= htmlspecialchars(Lang::t('theme_toggle')) ?>">
        <span id="theme-toggle-icon"><?= tc_icon($icons, 'sun') ?></span>
    </button>
</div>
<div class="container tc-fade-in">
    <?php if ($flash !== null): ?>
        <div class="alert alert-success tc-alert"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
<?php endif; ?>
<script>
(function () {
    var sunIcon = <?= json_encode(tc_icon($icons, 'sun')) ?>;
    var moonIcon = <?= json_encode(tc_icon($icons, 'moon')) ?>;
    var stored = localStorage.getItem('admin-theme');
    var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    function applyTheme(t) {
        document.documentElement.setAttribute('data-bs-theme', t);
        var iconSpan = document.getElementById('theme-toggle-icon');
        if (iconSpan) {
            iconSpan.innerHTML = t === 'dark' ? sunIcon : moonIcon;
        }
    }

    applyTheme(theme);

    var btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-bs-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('admin-theme', next);
        });
    }
})();
</script>
</body>
</html>
