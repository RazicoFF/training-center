<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ApplicationRepository;
use App\Repositories\ProfessionRepository;
use App\Services\TelegramNotifier;
use App\Services\UploadStore;

final class ApplicationController
{
    public function __construct(
        private readonly ApplicationRepository $repository = new ApplicationRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly UploadStore $uploads = new UploadStore(),
        private readonly TelegramNotifier $telegram = new TelegramNotifier()
    ) {
    }

    public function register(Router $router): void
    {
        $router->post('/api/v1/applications', fn (Request $req) => $this->store($req));
    }

    private function store(Request $request): array
    {
        $body = $request->jsonBody();
        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $professionId = (int) ($body['profession_id'] ?? 0);
        $brandId = ($body['brand_id'] ?? null) !== null ? (int) $body['brand_id'] : null;

        if ($fullName === '' || $phone === '' || $professionId <= 0) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'full_name, phone, profession_id required'],
                'status' => 422,
            ];
        }

        if ($this->professions->find($professionId) === null) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'profession_id does not exist'],
                'status' => 422,
            ];
        }

        $photoUrl = $this->handlePhotoUpload((string) ($body['photo_base64'] ?? ''));

        $id = $this->repository->create($fullName, $phone, $professionId, $brandId, $photoUrl);
        $this->telegram->notifyNewApplication($id);

        return ['id' => $id, 'status' => 201];
    }

    /**
     * Mirrors Site\HomeController::handlePhotoUpload() for the JSON API: the mobile app
     * has no multipart upload, so it sends the 3x4 photo as base64 instead (optionally as
     * a data: URI, e.g. "data:image/jpeg;base64,...", or as raw base64 with no prefix).
     */
    private function handlePhotoUpload(string $photoBase64): ?string
    {
        if ($photoBase64 === '') {
            return null;
        }

        // The image is re-encoded by UploadStore, so the declared type only matters for stripping the prefix.
        if (preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $photoBase64) === 1) {
            $photoBase64 = substr($photoBase64, strpos($photoBase64, ',') + 1);
        }

        $decoded = base64_decode($photoBase64, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        // A sane upper bound (5 MB) against an oversized payload being sent by mistake.
        if (strlen($decoded) > 5 * 1024 * 1024) {
            return null;
        }

        return $this->uploads->storeImageBytes($decoded, 'applications', 'application', UploadStore::PHOTO_MAX_SIDE);
    }
}
