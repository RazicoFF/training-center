<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProfessionBrandRepository
{
    public function forProfession(int $professionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, profession_id, name FROM profession_brands WHERE profession_id = ? ORDER BY sort_order, name'
        );
        $stmt->execute([$professionId]);

        return $stmt->fetchAll();
    }

    /**
     * All brands for all professions, keyed by profession_id, for the public
     * application form's client-side "profession selected -> show its brands" UI.
     *
     * @return array<int, array<int, array{id:int, name:string}>>
     */
    public function allGroupedByProfession(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, profession_id, name FROM profession_brands ORDER BY sort_order, name'
        );

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['profession_id']][] = ['id' => (int) $row['id'], 'name' => $row['name']];
        }

        return $grouped;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM profession_brands WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $professionId, string $name): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO profession_brands (profession_id, name) VALUES (?, ?)');
        $stmt->execute([$professionId, $name]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM profession_brands WHERE id = ?');
        $stmt->execute([$id]);
    }
}
