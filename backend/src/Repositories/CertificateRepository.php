<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\CertificatePdfService;

final class CertificateRepository
{
    public function __construct(private readonly CertificatePdfService $pdfService = new CertificatePdfService())
    {
    }

    public function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, user_id, profession_id, certificate_number, issue_date
             FROM certificates WHERE user_id = ? ORDER BY issue_date DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM certificates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function issue(int $userId, int $professionId): array
    {
        $pdo = Database::pdo();

        $user = (new UserRepository())->find($userId);
        $profession = (new ProfessionRepository())->find($professionId);

        if ($user === null || $profession === null) {
            throw new \RuntimeException('Cannot issue certificate: user or profession not found');
        }

        $certificateNumber = 'CERT-' . date('Y') . '-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT) . '-' . random_int(100, 999);
        $issueDate = date('Y-m-d');

        $pdfPath = $this->pdfService->generate([
            'full_name' => $user['full_name'],
            'profession_name' => $profession['name_uz'],
            'issue_date' => $issueDate,
            'certificate_number' => $certificateNumber,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO certificates (user_id, profession_id, certificate_number, issue_date, pdf_path) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $professionId, $certificateNumber, $issueDate, $pdfPath]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'certificate_number' => $certificateNumber,
            'issue_date' => $issueDate,
            'pdf_path' => $pdfPath,
        ];
    }
}
