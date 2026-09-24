<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ProfessionRepository;
use App\Repositories\ProfessionVideoRepository;

final class ProfessionController
{
    public function __construct(
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly ProfessionVideoRepository $videos = new ProfessionVideoRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/professions', fn (Request $req) => $this->index($req));
        $router->get('/admin/professions/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/professions', fn (Request $req) => $this->create($req));
        $router->get('/admin/professions/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/professions/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/professions/{id}/videos', fn (Request $req) => $this->addVideo($req));
        $router->post('/admin/professions/{id}/videos/{videoId}/delete', fn (Request $req) => $this->deleteVideo($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('professions/index', ['professions' => $this->professions->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('professions/create', []);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $nameUz = trim((string) ($body['name_uz'] ?? ''));
        $nameRu = trim((string) ($body['name_ru'] ?? ''));
        $descriptionUz = trim((string) ($body['description_uz'] ?? ''));
        $descriptionRu = trim((string) ($body['description_ru'] ?? ''));
        $durationDays = (int) ($body['duration_days'] ?? 0);
        $price = (float) ($body['price'] ?? 0);

        if ($nameUz === '' || $nameRu === '' || $durationDays <= 0 || $price <= 0) {
            return ['redirect' => '/admin/professions/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $careerInfoUz = trim((string) ($body['career_info_uz'] ?? ''));
        $careerInfoRu = trim((string) ($body['career_info_ru'] ?? ''));

        $professionId = $this->professions->create(
            $nameUz,
            $nameRu,
            $descriptionUz,
            $descriptionRu,
            $durationDays,
            $price,
            null,
            $careerInfoUz !== '' ? $careerInfoUz : null,
            $careerInfoRu !== '' ? $careerInfoRu : null
        );

        $imageUrl = $this->handleImageUpload($professionId);
        if ($imageUrl !== null) {
            $this->professions->updateImage($professionId, $imageUrl);
        }

        $pdfUrl = $this->handlePdfUpload($professionId);
        if ($pdfUrl !== null) {
            $this->professions->updatePdf($professionId, $pdfUrl);
        }

        return ['redirect' => "/admin/professions/{$professionId}/edit", 'flash' => Lang::t('profession_created')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) $request->param('id');
        $profession = $this->professions->find($professionId);

        if ($profession === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('professions/edit', [
            'profession' => $profession,
            'videos' => $this->videos->forProfession($professionId),
        ]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        if ($this->professions->find($professionId) === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $nameUz = trim((string) ($body['name_uz'] ?? ''));
        $nameRu = trim((string) ($body['name_ru'] ?? ''));
        $descriptionUz = trim((string) ($body['description_uz'] ?? ''));
        $descriptionRu = trim((string) ($body['description_ru'] ?? ''));
        $durationDays = (int) ($body['duration_days'] ?? 0);
        $price = (float) ($body['price'] ?? 0);

        if ($nameUz === '' || $nameRu === '' || $durationDays <= 0 || $price <= 0) {
            return ['redirect' => "/admin/professions/{$professionId}/edit", 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $careerInfoUz = trim((string) ($body['career_info_uz'] ?? ''));
        $careerInfoRu = trim((string) ($body['career_info_ru'] ?? ''));

        $this->professions->update(
            $professionId,
            $nameUz,
            $nameRu,
            $descriptionUz,
            $descriptionRu,
            $durationDays,
            $price,
            $careerInfoUz !== '' ? $careerInfoUz : null,
            $careerInfoRu !== '' ? $careerInfoRu : null
        );

        $imageUrl = $this->handleImageUpload($professionId);
        if ($imageUrl !== null) {
            $this->professions->updateImage($professionId, $imageUrl);
        }

        $pdfUrl = $this->handlePdfUpload($professionId);
        if ($pdfUrl !== null) {
            $this->professions->updatePdf($professionId, $pdfUrl);
        }

        return ['redirect' => '/admin/professions', 'flash' => Lang::t('profession_updated')];
    }

    private function addVideo(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $youtubeUrl = trim((string) ($body['youtube_url'] ?? ''));
        if ($youtubeUrl === '' || ProfessionVideoRepository::extractYoutubeId($youtubeUrl) === null) {
            return ['redirect' => "/admin/professions/{$professionId}/edit", 'flash' => Lang::t('video_invalid_url')];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        $this->videos->create(
            $professionId,
            $youtubeUrl,
            $titleUz !== '' ? $titleUz : null,
            $titleRu !== '' ? $titleRu : null
        );

        return ['redirect' => "/admin/professions/{$professionId}/edit", 'flash' => Lang::t('video_added')];
    }

    private function deleteVideo(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $this->videos->delete((int) $request->param('videoId'));

        return ['redirect' => "/admin/professions/{$professionId}/edit", 'flash' => Lang::t('video_deleted')];
    }

    private function handlePdfUpload(int $professionId): ?string
    {
        $uploadedPdf = $_FILES['pdf'] ?? null;
        if (!is_array($uploadedPdf) || ($uploadedPdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower((string) pathinfo((string) $uploadedPdf['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/professions';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'profession-' . $professionId . '-' . bin2hex(random_bytes(8)) . '.pdf';
        if (!move_uploaded_file((string) $uploadedPdf['tmp_name'], $uploadDir . '/' . $filename)) {
            return null;
        }

        return '/uploads/professions/' . $filename;
    }

    private function handleImageUpload(int $professionId): ?string
    {
        $uploadedImage = $_FILES['image'] ?? null;
        if (!is_array($uploadedImage) || ($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower((string) pathinfo((string) $uploadedImage['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/professions';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'profession-' . $professionId . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file((string) $uploadedImage['tmp_name'], $uploadDir . '/' . $filename)) {
            return null;
        }

        return '/uploads/professions/' . $filename;
    }
}
