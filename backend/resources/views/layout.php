<?php
/** @var string $content */
use App\Core\Csrf;
use App\Core\Lang;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$isLoggedIn = isset($_SESSION['admin_user_id']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::current()) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(Lang::t('app_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="/admin"><?= htmlspecialchars(Lang::t('app_title')) ?></a>
        <?php if ($isLoggedIn): ?>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="/admin/applications"><?= htmlspecialchars(Lang::t('nav_applications')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/groups"><?= htmlspecialchars(Lang::t('nav_groups')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/teachers"><?= htmlspecialchars(Lang::t('nav_teachers')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/tests"><?= htmlspecialchars(Lang::t('nav_tests')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/certificates"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></a>
            <button type="button" id="theme-toggle" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('theme_toggle')) ?></button>
            <form method="post" action="/admin/lang" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/admin') ?>">
                <input type="hidden" name="locale" value="<?= Lang::current() === 'uz' ? 'ru' : 'uz' ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <?= Lang::current() === 'uz' ? htmlspecialchars(Lang::t('lang_ru')) : htmlspecialchars(Lang::t('lang_uz')) ?>
                </button>
            </form>
            <form method="post" action="/admin/logout" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('logout')) ?></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
    <?php if ($flash !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
<script>
(function () {
    var stored = localStorage.getItem('admin-theme');
    var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', theme);
    var btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-bs-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('admin-theme', next);
        });
    }
})();
</script>
</body>
</html>
