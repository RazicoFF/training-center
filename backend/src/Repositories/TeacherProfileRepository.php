<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TeacherProfileRepository
{
    public function findByUserId(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM teacher_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Creates or updates the profile row for a teacher. All fields besides
     * user_id are optional (nullable), matching the requirement that teachers
     * are never forced to fill in age/experience/skills/education/contacts.
     */
    public function upsert(int $userId, array $fields): void
    {
        $columns = [
            'age',
            'experience_years',
            'skills_uz',
            'skills_ru',
            'education_uz',
            'education_ru',
            'telegram',
            'email',
            'photo_url',
        ];

        $values = [];
        foreach ($columns as $column) {
            $values[$column] = $fields[$column] ?? null;
        }

        $existing = $this->findByUserId($userId);

        if ($existing === null) {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO teacher_profiles (user_id, age, experience_years, skills_uz, skills_ru, education_uz, education_ru, telegram, email, photo_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $values['age'],
                $values['experience_years'],
                $values['skills_uz'],
                $values['skills_ru'],
                $values['education_uz'],
                $values['education_ru'],
                $values['telegram'],
                $values['email'],
                $values['photo_url'],
            ]);

            return;
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE teacher_profiles SET age = ?, experience_years = ?, skills_uz = ?, skills_ru = ?, education_uz = ?, education_ru = ?, telegram = ?, email = ?, photo_url = ? WHERE user_id = ?'
        );
        $stmt->execute([
            $values['age'],
            $values['experience_years'],
            $values['skills_uz'],
            $values['skills_ru'],
            $values['education_uz'],
            $values['education_ru'],
            $values['telegram'],
            $values['email'],
            $values['photo_url'],
            $userId,
        ]);
    }
}
