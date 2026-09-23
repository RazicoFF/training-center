<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Lang;
use PHPUnit\Framework\TestCase;

final class LangTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testDefaultsToUzAndTranslates(): void
    {
        $this->assertSame('uz', Lang::current());
        $this->assertSame('Boshqaruv paneli', Lang::t('nav_dashboard'));
    }

    public function testSetSwitchesLocale(): void
    {
        Lang::set('ru');

        $this->assertSame('ru', Lang::current());
        $this->assertSame('Панель управления', Lang::t('nav_dashboard'));
    }

    public function testSetIgnoresInvalidLocale(): void
    {
        Lang::set('ru');
        Lang::set('fr');

        $this->assertSame('ru', Lang::current());
    }

    public function testUnknownKeyReturnsTheKeyItself(): void
    {
        $this->assertSame('no_such_key', Lang::t('no_such_key'));
    }
}
