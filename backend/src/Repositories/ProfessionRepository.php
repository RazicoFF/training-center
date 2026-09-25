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

    /**
     * True if this profession has real learner-history records attached (groups, tests,
     * certificates) that would be silently orphaned by a delete. Applications aren't
     * checked - they're not yet committed learner records and are cleaned up
     * automatically by delete(), same as brand and video rows.
     */
    public function hasDependents(int $id): bool
    {
        $pdo = Database::pdo();
        $checks = [
            'SELECT COUNT(*) FROM `groups` WHERE profession_id = ?',
            'SELECT COUNT(*) FROM tests WHERE profession_id = ?',
            'SELECT COUNT(*) FROM certificates WHERE profession_id = ?',
        ];

        foreach ($checks as $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }

        return false;
    }

    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        // applications.profession_id is NOT NULL and applications.brand_id references
        // profession_brands, so applications must be cleared before either of those.
        $pdo->prepare('DELETE FROM applications WHERE profession_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM profession_videos WHERE profession_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM profession_brands WHERE profession_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM professions WHERE id = ?')->execute([$id]);
    }
}
