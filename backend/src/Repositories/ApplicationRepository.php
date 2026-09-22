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

        $userId = $this->users->create(
            $application['full_name'],
            $application['phone'],
            Auth::hashPassword($temporaryPassword),
            'student'
        );

        $stmt = Database::pdo()->prepare(
            "UPDATE applications SET status = 'approved', created_user_id = ? WHERE id = ?"
        );
        $stmt->execute([$userId, $applicationId]);

        return ['user_id' => $userId, 'phone' => $application['phone']];
    }
}
