<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProfessionVideoRepository
{
    public function forProfession(int $professionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, profession_id, youtube_url, title_uz, title_ru, sort_order
             FROM profession_videos WHERE profession_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$professionId]);

        return $stmt->fetchAll();
    }

    public function create(int $professionId, string $youtubeUrl, ?string $titleUz, ?string $titleRu): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO profession_videos (profession_id, youtube_url, title_uz, title_ru) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $youtubeUrl, $titleUz, $titleRu]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM profession_videos WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Extracts the YouTube video id from any common URL shape (watch?v=, youtu.be/, embed/)
     * so the site/app can build an embeddable player URL regardless of how the admin pasted it.
     */
    public static function extractYoutubeId(string $url): ?string
    {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
