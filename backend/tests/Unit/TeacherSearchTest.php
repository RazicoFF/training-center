<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class TeacherSearchTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998987770110'");
        (new UserRepository())->create('Searchable Teacher', '+998987770110', 'x', 'teacher');
    }

    public function testSearchFindsByNameSubstring(): void
    {
        $repo = new TeacherRepository();

        $results = $repo->search('Searchable');
        $names = array_column($results, 'full_name');
        $this->assertContains('Searchable Teacher', $names);
    }

    public function testSearchFindsByPhoneSubstring(): void
    {
        $repo = new TeacherRepository();

        $results = $repo->search('987770110');
        $names = array_column($results, 'full_name');
        $this->assertContains('Searchable Teacher', $names);
    }

    public function testSearchWithNoMatchReturnsEmpty(): void
    {
        $repo = new TeacherRepository();

        $results = $repo->search('NoSuchTeacherXYZ');
        $this->assertSame([], $results);
    }
}
