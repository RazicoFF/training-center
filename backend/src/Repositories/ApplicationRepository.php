<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class ApplicationRepository
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function create(string $fullName, string $phone, int $professionId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO applications (full_name, phone, profession_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([$fullName, $phone, $professionId]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{user_id:int, phone:string}
     */
    public function approve(int $applicationId, string $temporaryPassword): array
    {
        $application = $this->find($applicationId);
        if ($application === null || $application['status'] !== 'pending') {
            throw new \RuntimeException('Application not pending');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $userId = $this->users->create(
                $application['full_name'],
                $application['phone'],
                Auth::hashPassword($temporaryPassword),
                'student'
            );

            $stmt = $pdo->prepare(
                "UPDATE applications SET status = 'approved', created_user_id = ? WHERE id = ?"
            );
            $stmt->execute([$userId, $applicationId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['user_id' => $userId, 'phone' => $application['phone']];
    }

    public function allWithProfession(?string $status = null): array
    {
        $sql = 'SELECT a.*, p.name_uz AS profession_name_uz, p.name_ru AS profession_name_ru
                FROM applications a JOIN professions p ON p.id = a.profession_id';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE a.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY a.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function reject(int $id): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE applications SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
