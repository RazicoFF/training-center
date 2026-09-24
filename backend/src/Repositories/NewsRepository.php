<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class NewsRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM news ORDER BY published_at DESC');

        return $stmt->fetchAll();
    }

    public function latest(int $limit = 6): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM news ORDER BY published_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM news WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $titleUz, string $titleRu, ?string $bodyUz, ?string $bodyRu, ?string $imageUrl): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO news (title_uz, title_ru, body_uz, body_ru, image_url) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$titleUz, $titleRu, $bodyUz, $bodyRu, $imageUrl]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, string $titleUz, string $titleRu, ?string $bodyUz, ?string $bodyRu): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE news SET title_uz = ?, title_ru = ?, body_uz = ?, body_ru = ? WHERE id = ?'
        );
        $stmt->execute([$titleUz, $titleRu, $bodyUz, $bodyRu, $id]);
    }

    public function updateImage(int $id, string $imageUrl): void
    {
        $stmt = Database::pdo()->prepare('UPDATE news SET image_url = ? WHERE id = ?');
        $stmt->execute([$imageUrl, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM news WHERE id = ?');
        $stmt->execute([$id]);
    }
}
