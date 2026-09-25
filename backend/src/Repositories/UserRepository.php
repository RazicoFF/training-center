<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function create(string $fullName, string $phone, string $passwordHash, string $role = 'student'): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $phone, $passwordHash, $role]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function allByRole(string $role): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, full_name, phone, photo_url, created_at FROM users WHERE role = ? ORDER BY full_name'
        );
        $stmt->execute([$role]);

        return $stmt->fetchAll();
    }

    public function updatePhoto(int $id, string $photoUrl): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET photo_url = ? WHERE id = ?');
        $stmt->execute([$photoUrl, $id]);
    }

    public function updateProfile(int $id, string $fullName, string $phone): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Removing a student also erases their learning history (enrollments, test attempts,
     * the exact questions they were shown, and certificates) since none of it means
     * anything once the account is gone. Their original application record is kept for
     * the admin's records, just detached from the now-deleted user.
     */
    public function deleteStudentCascade(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE applications SET created_user_id = NULL WHERE created_user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM certificates WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM test_attempts WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM test_question_selections WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM enrollments WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
