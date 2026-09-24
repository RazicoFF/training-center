<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\CertificateRepository;
use PHPUnit\Framework\TestCase;

final class CertificateFilterTest extends TestCase
{
    private int $studentId;
    private int $professionId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM certificates WHERE certificate_number = 'CERT-FILTER-TEST'");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770120'");
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Filter Test Student', '+998987770120', 'x', 'student')")
            ->execute();
        $this->studentId = (int) $pdo->lastInsertId();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $pdo->prepare(
            "INSERT INTO certificates (user_id, profession_id, certificate_number, issue_date, pdf_path)
             VALUES (?, ?, 'CERT-FILTER-TEST', '2026-05-15', '/tmp/x.pdf')"
        )->execute([$this->studentId, $this->professionId]);
    }

    public function testFiltersByNameSearch(): void
    {
        $repo = new CertificateRepository();

        $results = $repo->allWithDetails('Filter Test Student');
        $this->assertNotEmpty($results);
        $this->assertSame('CERT-FILTER-TEST', $results[0]['certificate_number']);
    }

    public function testFiltersByProfessionAndYear(): void
    {
        $repo = new CertificateRepository();

        $byProfession = $repo->allWithDetails(null, $this->professionId, null);
        $numbers = array_column($byProfession, 'certificate_number');
        $this->assertContains('CERT-FILTER-TEST', $numbers);

        $byYear = $repo->allWithDetails(null, null, 2026);
        $numbers = array_column($byYear, 'certificate_number');
        $this->assertContains('CERT-FILTER-TEST', $numbers);

        $wrongYear = $repo->allWithDetails(null, null, 1999);
        $numbers = array_column($wrongYear, 'certificate_number');
        $this->assertNotContains('CERT-FILTER-TEST', $numbers);
    }

    public function testDistinctYearsIncludesFixtureYear(): void
    {
        $repo = new CertificateRepository();

        $this->assertContains(2026, $repo->distinctYears());
    }
}
