<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProfessionRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, career_info_uz, career_info_ru FROM professions ORDER BY id'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, career_info_uz, career_info_ru FROM professions WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function updateCareerInfo(int $id, ?string $careerInfoUz, ?string $careerInfoRu): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE professions SET career_info_uz = ?, career_info_ru = ? WHERE id = ?'
        );
        $stmt->execute([$careerInfoUz, $careerInfoRu, $id]);
    }
}
