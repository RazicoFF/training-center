<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableAcrossCallsAndVerifies(): void
    {
        $token = Csrf::token();

        $this->assertSame($token, Csrf::token());
        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsWrongOrMissingToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('wrong-token'));
        $this->assertFalse(Csrf::verify(null));
    }
}
