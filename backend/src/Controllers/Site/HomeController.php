<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Repositories\ApplicationRepository;
use App\Repositories\NewsRepository;
use App\Repositories\ProfessionBrandRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\SiteSettingsRepository;
use App\Services\UploadStore;

final class HomeController
{
    public function __construct(
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly ApplicationRepository $applications = new ApplicationRepository(),
        private readonly SiteSettingsRepository $settings = new SiteSettingsRepository(),
        private readonly NewsRepository $news = new NewsRepository(),
        private readonly ProfessionBrandRepository $brands = new ProfessionBrandRepository(),
        private readonly UploadStore $uploads = new UploadStore()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/', fn (Request $req) => $this->home($req));
        $router->get('/apply', fn (Request $req) => $this->applyForm($req));
        $router->post('/apply', fn (Request $req) => $this->apply($req));
        $router->get('/sitemap.xml', fn (Request $req) => $this->sitemap($req));
    }

    private function home(Request $request): array
    {
        $settings = $this->settings->get();
        SiteView::render('site/home', [
            'professions' => $this->professions->all(),
            'settings' => $settings,
            'newsItems' => $this->news->latest(),
            'meta' => [
                'description' => Lang::current() === 'ru' ? ($settings['about_ru'] ?? null) : ($settings['about_uz'] ?? null),
            ],
        ]);
        return ['rendered' => true];
    }

    private function applyForm(Request $request): array
    {
        SiteView::render('site/apply', [
            'professions' => $this->professions->all(),
            'brandsByProfession' => $this->brands->allGroupedByProfession(),
            'selectedProfessionId' => (int) ($_GET['profession_id'] ?? 0),
            'submitted' => false,
            'meta' => ['title' => Lang::t('site_nav_apply'), 'description' => Lang::t('site_meta_apply')],
        ]);
        return ['rendered' => true];
    }

    /** Public pages for search engines; robots.txt points here. */
    private function sitemap(Request $request): array
    {
        $scheme = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $paths = ['/', '/teachers', '/media', '/apply'];
        foreach ($this->professions->all() as $profession) {
            $paths[] = '/professions/' . (int) $profession['id'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($paths as $path) {
            echo '  <url><loc>' . htmlspecialchars($baseUrl . $path, ENT_XML1) . '</loc></url>' . "\n";
        }
        echo '</urlset>' . "\n";

        return ['rendered' => true];
    }

    private function apply(Request $request): array
    {
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/apply'];
        }

        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $professionId = (int) ($body['profession_id'] ?? 0);
        $brandId = ($body['brand_id'] ?? '') !== '' ? (int) $body['brand_id'] : null;

        if ($fullName === '' || $phone === '' || $professionId <= 0) {
            SiteView::render('site/apply', [
                'professions' => $this->professions->all(),
                'brandsByProfession' => $this->brands->allGroupedByProfession(),
                'selectedProfessionId' => $professionId,
                'submitted' => false,
                'error' => Lang::t('apply_error'),
            ]);
            return ['rendered' => true];
        }

        $photoUrl = $this->handlePhotoUpload();

        $this->applications->create($fullName, $phone, $professionId, $brandId, $photoUrl);

        SiteView::render('site/apply', [
            'professions' => $this->professions->all(),
            'brandsByProfession' => $this->brands->allGroupedByProfession(),
            'selectedProfessionId' => 0,
            'submitted' => true,
        ]);
        return ['rendered' => true];
    }

    private function handlePhotoUpload(): ?string
    {
        return $this->uploads->storeUploadedImage('photo', 'applications', 'application', UploadStore::PHOTO_MAX_SIDE);
    }
}
