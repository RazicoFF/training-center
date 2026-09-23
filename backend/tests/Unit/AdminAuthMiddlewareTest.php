<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\AdminAuthMiddleware;
use PHPUnit\Framework\TestCase;

final class AdminAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testReturnsNullWhenSessionIsEmpty(): void
    {
        $this->assertNull(AdminAuthMiddleware::authenticate());
    }

    public function testReturnsClaimsWhenSessionIsSet(): void
    {
        $_SESSION['admin_user_id'] = 5;
        $_SESSION['admin_role'] = 'admin';

        $this->assertSame(['user_id' => 5, 'role' => 'admin'], AdminAuthMiddleware::authenticate());
    }

    public function testReturnsNullWhenOnlyOneKeyIsSet(): void
    {
        $_SESSION['admin_user_id'] = 5;

        $this->assertNull(AdminAuthMiddleware::authenticate());
    }

    public function testRequireAdminReturnsNullForTeacherRole(): void
    {
        $_SESSION['admin_user_id'] = 5;
        $_SESSION['admin_role'] = 'teacher';

        $this->assertNull(AdminAuthMiddleware::requireAdmin());
    }

    public function testRequireAdminReturnsClaimsForAdminRole(): void
    {
        $_SESSION['admin_user_id'] = 5;
        $_SESSION['admin_role'] = 'admin';

        $this->assertSame(['user_id' => 5, 'role' => 'admin'], AdminAuthMiddleware::requireAdmin());
    }

    public function testRequireAdminReturnsNullWhenSessionIsEmpty(): void
    {
        $this->assertNull(AdminAuthMiddleware::requireAdmin());
    }
}
