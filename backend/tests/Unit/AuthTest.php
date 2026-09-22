<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testHashAndVerifyPassword(): void
    {
        $hash = Auth::hashPassword('secret123');

        $this->assertTrue(Auth::verifyPassword('secret123', $hash));
        $this->assertFalse(Auth::verifyPassword('wrong', $hash));
    }

    public function testIssueAndVerifyToken(): void
    {
        $token = Auth::issueToken(7, 'student');
        $claims = Auth::verifyToken($token);

        $this->assertSame(7, $claims['user_id']);
        $this->assertSame('student', $claims['role']);
    }

    public function testVerifyTokenReturnsNullForGarbage(): void
    {
        $this->assertNull(Auth::verifyToken('not-a-real-token'));
    }
}
