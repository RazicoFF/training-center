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

final class HomeController
{
    public function __construct(
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly ApplicationRepository $applications = new ApplicationRepository(),
        private readonly SiteSettingsRepository $settings = new SiteSettingsRepository(),
        private readonly NewsRepository $news = new NewsRepository(),
        private readonly ProfessionBrandRepository $brands = new ProfessionBrandRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/', fn (Request $req) => $this->home($req));
        $router->get('/apply', fn (Request $req) => $this->applyForm($req));
        $router->post('/apply', fn (Request $req) => $this->apply($req));
    }

    private function home(Request $request): array
    {
        SiteView::render('site/home', [
            'professions' => $this->professions->all(),
            'settings' => $this->settings->get(),
            'newsItems' => $this->news->latest(),
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
        ]);
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
        $uploadedPhoto = $_FILES['photo'] ?? null;
        if (!is_array($uploadedPhoto) || ($uploadedPhoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower((string) pathinfo((string) $uploadedPhoto['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/applications';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'application-' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file((string) $uploadedPhoto['tmp_name'], $uploadDir . '/' . $filename)) {
            return null;
        }

        return '/uploads/applications/' . $filename;
    }
}
