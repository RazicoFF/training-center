<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

final class ScheduleGeneratorTest extends TestCase
{
    private int $groupId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM schedule');
        $pdo->exec('DELETE FROM enrollments');
        $pdo->exec('DELETE FROM `groups`');
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "SchedGen", "2026-01-05", "2026-01-18")')
            ->execute([$professionId]);
        $this->groupId = (int) $pdo->lastInsertId();
    }

    public function testGeneratesOneRowPerMatchingWeekdayInRange(): void
    {
        // 2026-01-05 is a Monday. Range is exactly 2 weeks (Mon 5 - Sun 18).
        // Template: Monday (1) and Wednesday (3) -> 2 occurrences each = 4 rows.
        $generator = new ScheduleGenerator();

        $count = $generator->generateForGroup(
            $this->groupId,
            new \DateTimeImmutable('2026-01-05'),
            new \DateTimeImmutable('2026-01-18'),
            [
                ['weekday' => 1, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
                ['weekday' => 3, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
            ]
        );

        $this->assertSame(4, $count);

        $dates = Database::pdo()
            ->query("SELECT lesson_date FROM schedule WHERE group_id = {$this->groupId} ORDER BY lesson_date")
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertSame(['2026-01-05', '2026-01-07', '2026-01-12', '2026-01-14'], $dates);
    }

    public function testEmptyTemplateGeneratesNothing(): void
    {
        $generator = new ScheduleGenerator();

        $count = $generator->generateForGroup(
            $this->groupId,
            new \DateTimeImmutable('2026-01-05'),
            new \DateTimeImmutable('2026-01-18'),
            []
        );

        $this->assertSame(0, $count);
    }
}
