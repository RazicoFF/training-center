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

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM media_items WHERE id = ?');
        $stmt->execute([$id]);
    }
}
