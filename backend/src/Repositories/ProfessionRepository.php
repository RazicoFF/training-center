<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProfessionRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, pdf_url, career_info_uz, career_info_ru FROM professions ORDER BY id'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, pdf_url, career_info_uz, career_info_ru FROM professions WHERE id = ?'
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

    public function create(
        string $nameUz,
        string $nameRu,
        string $descriptionUz,
        string $descriptionRu,
        int $durationDays,
        float $price,
        ?string $imageUrl,
        ?string $careerInfoUz,
        ?string $careerInfoRu
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, career_info_uz, career_info_ru)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nameUz, $nameRu, $descriptionUz, $descriptionRu, $durationDays, $price, $imageUrl, $careerInfoUz, $careerInfoRu]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(
        int $id,
        string $nameUz,
        string $nameRu,
        string $descriptionUz,
        string $descriptionRu,
        int $durationDays,
        float $price,
        ?string $careerInfoUz,
        ?string $careerInfoRu
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE professions
             SET name_uz = ?, name_ru = ?, description_uz = ?, description_ru = ?, duration_days = ?, price = ?, career_info_uz = ?, career_info_ru = ?
             WHERE id = ?'
        );
        $stmt->execute([$nameUz, $nameRu, $descriptionUz, $descriptionRu, $durationDays, $price, $careerInfoUz, $careerInfoRu, $id]);
    }

    public function updateImage(int $id, string $imageUrl): void
    {
        $stmt = Database::pdo()->prepare('UPDATE professions SET image_url = ? WHERE id = ?');
        $stmt->execute([$imageUrl, $id]);
    }

    public function updatePdf(int $id, string $pdfUrl): void
    {
        $stmt = Database::pdo()->prepare('UPDATE professions SET pdf_url = ? WHERE id = ?');
        $stmt->execute([$pdfUrl, $id]);
    }
}
