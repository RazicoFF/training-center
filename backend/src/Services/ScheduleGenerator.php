<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;

final class ScheduleGenerator
{
    /**
     * @param array<int, array{weekday:int, start_time:string, end_time:string, room:string}> $weeklyTemplate
     */
    public function generateForGroup(int $groupId, DateTimeImmutable $start, DateTimeImmutable $end, array $weeklyTemplate): int
    {
        if ($weeklyTemplate === []) {
            return 0;
        }

        $byWeekday = [];
        foreach ($weeklyTemplate as $entry) {
            $byWeekday[$entry['weekday']][] = $entry;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO schedule (group_id, lesson_date, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)'
        );

        $count = 0;
        $current = $start;
        $oneDay = new DateInterval('P1D');

        while ($current <= $end) {
            $weekday = (int) $current->format('N');

            foreach ($byWeekday[$weekday] ?? [] as $entry) {
                $stmt->execute([$groupId, $current->format('Y-m-d'), $entry['start_time'], $entry['end_time'], $entry['room']]);
                $count++;
            }

            $current = $current->add($oneDay);
        }

        return $count;
    }
}
