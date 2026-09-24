<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\MediaRepository;
use App\Repositories\ProfessionVideoRepository;

final class MediaController
{
    public function __construct(
        private readonly MediaRepository $media = new MediaRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/media', fn (Request $req) => $this->index($req));
        $router->post('/admin/media/image', fn (Request $req) => $this->createImage($req));
        $router->post('/admin/media/video', fn (Request $req) => $this->createVideo($req));
        $router->post('/admin/media/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('media/index', ['mediaItems' => $this->media->all()]);
        return ['rendered' => true];
    }

    private function createImage(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $uploadedImage = $_FILES['image'] ?? null;
        if (!is_array($uploadedImage) || ($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['redirect' => '/admin/media', 'flash' => Lang::t('media_image_required')];
        }

        $extension = strtolower((string) pathinfo((string) $uploadedImage['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['redirect' => '/admin/media', 'flash' => Lang::t('media_image_required')];
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/media';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'media-' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file((string) $uploadedImage['tmp_name'], $uploadDir . '/' . $filename)) {
            return ['redirect' => '/admin/media', 'flash' => Lang::t('media_image_required')];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        $this->media->createImage(
            '/uploads/media/' . $filename,
            $titleUz !== '' ? $titleUz : null,
            $titleRu !== '' ? $titleRu : null
        );

        return ['redirect' => '/admin/media', 'flash' => Lang::t('media_added')];
    }

    private function createVideo(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $youtubeUrl = trim((string) ($body['youtube_url'] ?? ''));
        if ($youtubeUrl === '' || ProfessionVideoRepository::extractYoutubeId($youtubeUrl) === null) {
            return ['redirect' => '/admin/media', 'flash' => Lang::t('video_invalid_url')];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        $this->media->createVideo(
            $youtubeUrl,
            $titleUz !== '' ? $titleUz : null,
            $titleRu !== '' ? $titleRu : null
        );

        return ['redirect' => '/admin/media', 'flash' => Lang::t('media_added')];
    }

    private function delete(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $this->media->delete((int) $request->param('id'));

        return ['redirect' => '/admin/media', 'flash' => Lang::t('media_deleted')];
    }
}
