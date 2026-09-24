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
     * All teachers (users.role = 'teacher') left-joined with their optional profile,
     * for the public "our teachers" page. Fields the teacher never filled in come back
     * as null so the view can skip rendering them.
     */
    public function allTeachersWithProfiles(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id AS user_id, u.full_name,
                    tp.age, tp.experience_years, tp.skills_uz, tp.skills_ru,
                    tp.education_uz, tp.education_ru, tp.telegram, tp.email, tp.photo_url
             FROM users u
             LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
             WHERE u.role = \'teacher\'
             ORDER BY u.full_name'
        );

        return $stmt->fetchAll();
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
