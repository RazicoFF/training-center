<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    public function testPdoReturnsSameInstanceAndCanQuery(): void
    {
        $pdo1 = Database::pdo();
        $pdo2 = Database::pdo();

        $this->assertSame($pdo1, $pdo2);
        $this->assertSame(1, (int) $pdo1->query('SELECT 1')->fetchColumn());
    }
}
