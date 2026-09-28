<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\NewsRepository;
use App\Services\UploadStore;

final class NewsController
{
    public function __construct(
        private readonly NewsRepository $news = new NewsRepository(),
        private readonly UploadStore $uploads = new UploadStore()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/news', fn (Request $req) => $this->index($req));
        $router->get('/admin/news/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/news', fn (Request $req) => $this->create($req));
        $router->get('/admin/news/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/news/{id}', fn (Request $req) => $this->update($req));
        $router->post('/admin/news/{id}/delete', fn (Request $req) => $this->delete($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('news/index', ['newsItems' => $this->news->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('news/create', []);
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

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        if ($titleUz === '' || $titleRu === '') {
            return ['redirect' => '/admin/news/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring', 'flash_type' => 'error'];
        }

        $bodyUz = trim((string) ($body['body_uz'] ?? ''));
        $bodyRu = trim((string) ($body['body_ru'] ?? ''));

        $newsId = $this->news->create(
            $titleUz,
            $titleRu,
            $bodyUz !== '' ? $bodyUz : null,
            $bodyRu !== '' ? $bodyRu : null,
            null
        );

        $imageUrl = $this->handleImageUpload($newsId);
        if ($imageUrl !== null) {
            $this->news->updateImage($newsId, $imageUrl);
        }

        return ['redirect' => '/admin/news', 'flash' => Lang::t('news_created')];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $item = $this->news->find((int) $request->param('id'));

        if ($item === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('news/edit', ['item' => $item]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $newsId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $existing = $this->news->find($newsId);
        if ($existing === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));

        if ($titleUz === '' || $titleRu === '') {
            return ['redirect' => "/admin/news/{$newsId}/edit", 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring', 'flash_type' => 'error'];
        }

        $bodyUz = trim((string) ($body['body_uz'] ?? ''));
        $bodyRu = trim((string) ($body['body_ru'] ?? ''));

        $this->news->update($newsId, $titleUz, $titleRu, $bodyUz !== '' ? $bodyUz : null, $bodyRu !== '' ? $bodyRu : null);

        $imageUrl = $this->handleImageUpload($newsId);
        if ($imageUrl !== null) {
            $this->news->updateImage($newsId, $imageUrl);
            $this->uploads->delete($existing['image_url']);
        }

        return ['redirect' => '/admin/news', 'flash' => Lang::t('news_updated')];
    }

    private function delete(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $newsId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $item = $this->news->find($newsId);
        $this->news->delete($newsId);
        $this->uploads->delete($item['image_url'] ?? null);

        return ['redirect' => '/admin/news', 'flash' => Lang::t('news_deleted')];
    }

    private function handleImageUpload(int $newsId): ?string
    {
        return $this->uploads->storeUploadedImage('image', 'news', 'news-' . $newsId);
    }
}
