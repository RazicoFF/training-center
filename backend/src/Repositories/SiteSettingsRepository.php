<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class SiteSettingsRepository
{
    public function get(): array
    {
        $row = Database::pdo()->query('SELECT * FROM site_settings WHERE id = 1')->fetch();

        return $row === false ? [] : $row;
    }

    public function update(array $fields): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE site_settings SET
                address_uz = ?, address_ru = ?, map_embed_url = ?,
                telegram = ?, email = ?, phone = ?,
                about_uz = ?, about_ru = ?,
                stat_graduates = ?, stat_years = ?, stat_employment_percent = ?
             WHERE id = 1'
        );
        $stmt->execute([
            $fields['address_uz'] ?? null,
            $fields['address_ru'] ?? null,
            $fields['map_embed_url'] ?? null,
            $fields['telegram'] ?? null,
            $fields['email'] ?? null,
            $fields['phone'] ?? null,
            $fields['about_uz'] ?? null,
            $fields['about_ru'] ?? null,
            $fields['stat_graduates'] ?? null,
            $fields['stat_years'] ?? null,
            $fields['stat_employment_percent'] ?? null,
        ]);
    }
}
