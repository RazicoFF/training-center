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
use App\Services\UploadStore;

final class MediaController
{
    public function __construct(
        private readonly MediaRepository $media = new MediaRepository(),
        private readonly UploadStore $uploads = new UploadStore()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/media', fn (Request $req) => $this->index($req));
        $router->post('/admin/media/image', fn (Request $req) => $this->createImage($req));
        $router->post('/admin/media/video', fn (Request $req) => $this->createVideo($req));
        $router->get('/admin/media/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/media/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/media/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
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

        $fileUrl = $this->storeUploadedImage();
        if ($fileUrl === null) {
            return ['redirect' => '/admin/media', 'flash' => Lang::t('media_image_required'), 'flash_type' => 'error'];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        $this->media->createImage(
            $fileUrl,
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
            return ['redirect' => '/admin/media', 'flash' => Lang::t('video_invalid_url'), 'flash_type' => 'error'];
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

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $item = $this->media->find((int) $request->param('id'));
        if ($item === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('media/edit', ['item' => $item]);
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

        $mediaId = (int) $request->param('id');
        $item = $this->media->find($mediaId);
        if ($item === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $fileUrl = null;
        $youtubeUrl = null;
        if ($item['type'] === 'image') {
            $uploadedImage = $_FILES['image'] ?? null;
            if (is_array($uploadedImage) && ($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $fileUrl = $this->storeUploadedImage();
                if ($fileUrl === null) {
                    return ['redirect' => "/admin/media/{$mediaId}/edit", 'flash' => Lang::t('media_image_required'), 'flash_type' => 'error'];
                }
            }
        } else {
            $youtubeUrl = trim((string) ($body['youtube_url'] ?? ''));
            if ($youtubeUrl === '' || ProfessionVideoRepository::extractYoutubeId($youtubeUrl) === null) {
                return ['redirect' => "/admin/media/{$mediaId}/edit", 'flash' => Lang::t('video_invalid_url'), 'flash_type' => 'error'];
            }
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        $this->media->update(
            $mediaId,
            $titleUz !== '' ? $titleUz : null,
            $titleRu !== '' ? $titleRu : null,
            (int) ($body['sort_order'] ?? 0),
            $fileUrl,
            $youtubeUrl
        );
        if ($fileUrl !== null) {
            $this->uploads->delete($item['file_url']);
        }

        return ['redirect' => '/admin/media', 'flash' => Lang::t('media_updated')];
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

        $item = $this->media->find((int) $request->param('id'));
        $this->media->delete((int) $request->param('id'));
        $this->uploads->delete($item['file_url'] ?? null);

        return ['redirect' => '/admin/media', 'flash' => Lang::t('media_deleted')];
    }

    /**
     * Moves the uploaded $_FILES['image'] into public/uploads/media and returns its
     * public URL, or null when no valid jpg/png/webp file was uploaded.
     */
    private function storeUploadedImage(): ?string
    {
        return $this->uploads->storeUploadedImage('image', 'media', 'media');
    }
}
