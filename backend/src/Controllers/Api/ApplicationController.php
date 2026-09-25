<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ApplicationRepository;
use App\Repositories\ProfessionRepository;

final class ApplicationController
{
    public function __construct(
        private readonly ApplicationRepository $repository = new ApplicationRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
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

        $extension = 'jpg';
        if (preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $photoBase64, $matches) === 1) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
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

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/applications';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'application-' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (file_put_contents($uploadDir . '/' . $filename, $decoded) === false) {
            return null;
        }

        return '/uploads/applications/' . $filename;
    }
}
