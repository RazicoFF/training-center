<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestScheduleWindowTest extends TestCase
{
    private TestRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TestRepository();
    }

    public function testOpenWhenNoScheduleSet(): void
    {
        $this->assertTrue($this->repository->isOpenNow(['opens_at' => null, 'closes_at' => null]));
    }

    public function testClosedBeforeOpensAt(): void
    {
        $future = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        $this->assertFalse($this->repository->isOpenNow(['opens_at' => $future, 'closes_at' => null]));
    }

    public function testClosedAfterClosesAt(): void
    {
        $past = (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s');
        $this->assertFalse($this->repository->isOpenNow(['opens_at' => null, 'closes_at' => $past]));
    }

    public function testOpenWithinWindow(): void
    {
        $opensAt = (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s');
        $closesAt = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->assertTrue($this->repository->isOpenNow(['opens_at' => $opensAt, 'closes_at' => $closesAt]));
    }
}
