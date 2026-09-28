<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\SiteSettingsRepository;
use App\Services\DatabaseBackup;

final class SiteSettingsController
{
    public function __construct(
        private readonly SiteSettingsRepository $settings = new SiteSettingsRepository(),
        private readonly DatabaseBackup $backups = new DatabaseBackup()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/settings', fn (Request $req) => $this->edit($req));
        $router->post('/admin/settings', fn (Request $req) => $this->update($req));
        $router->post('/admin/settings/backup', fn (Request $req) => $this->backup($req));
    }

    /**
     * Streams a gzipped SQL dump of the whole database (uploaded images included).
     * POST + CSRF so a stray link or prefetch can never trigger the heavy export.
     */
    private function backup(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }
        if (!Csrf::verify($request->formBody()['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        @set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="backup-' . date('Y-m-d-His') . '.sql.gz"');
        header('Cache-Control: no-store');

        $gzip = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 6]);
        $this->backups->dump(function (string $chunk) use ($gzip): void {
            echo deflate_add($gzip, $chunk, ZLIB_NO_FLUSH);
        });
        echo deflate_add($gzip, '', ZLIB_FINISH);

        return ['rendered' => true];
    }

    private function edit(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('settings/edit', ['settings' => $this->settings->get()]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $trim = static fn (string $key): ?string => trim((string) ($body[$key] ?? '')) !== '' ? trim((string) $body[$key]) : null;
        $int = static fn (string $key): ?int => trim((string) ($body[$key] ?? '')) !== '' ? (int) $body[$key] : null;

        $this->settings->update([
            'address_uz' => $trim('address_uz'),
            'address_ru' => $trim('address_ru'),
            'map_embed_url' => $trim('map_embed_url'),
            'telegram' => $trim('telegram'),
            'email' => $trim('email'),
            'phone' => $trim('phone'),
            'about_uz' => $trim('about_uz'),
            'about_ru' => $trim('about_ru'),
            'stat_graduates' => $int('stat_graduates'),
            'stat_years' => $int('stat_years'),
            'stat_employment_percent' => $int('stat_employment_percent'),
        ]);

        return ['redirect' => '/admin/settings', 'flash' => Lang::t('settings_updated')];
    }
}
