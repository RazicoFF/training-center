<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class MediaRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM media_items ORDER BY sort_order, id DESC');

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM media_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function createImage(string $fileUrl, ?string $titleUz, ?string $titleRu): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO media_items (type, file_url, title_uz, title_ru) VALUES ('image', ?, ?, ?)"
        );
        $stmt->execute([$fileUrl, $titleUz, $titleRu]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function createVideo(string $youtubeUrl, ?string $titleUz, ?string $titleRu): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO media_items (type, youtube_url, title_uz, title_ru) VALUES ('video', ?, ?, ?)"
        );
        $stmt->execute([$youtubeUrl, $titleUz, $titleRu]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Updates titles and sort order; $fileUrl / $youtubeUrl replace the item's source
     * only when non-null, so an edit without a new upload keeps the current one.
     */
    public function update(int $id, ?string $titleUz, ?string $titleRu, int $sortOrder, ?string $fileUrl = null, ?string $youtubeUrl = null): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE media_items
             SET title_uz = ?, title_ru = ?, sort_order = ?,
                 file_url = COALESCE(?, file_url), youtube_url = COALESCE(?, youtube_url)
             WHERE id = ?'
        );
        $stmt->execute([$titleUz, $titleRu, $sortOrder, $fileUrl, $youtubeUrl, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM media_items WHERE id = ?');
        $stmt->execute([$id]);
    }
}
