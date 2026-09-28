<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\TelegramNotifier;
use PHPUnit\Framework\TestCase;

final class TelegramNotifierTest extends TestCase
{
    private int $applicationId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998900007777'");
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id) VALUES (?, ?, ?)')
            ->execute(['Ali <Valiyev>', '+998900007777', $professionId]);
        $this->applicationId = (int) $pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        Database::pdo()->exec("DELETE FROM applications WHERE phone = '+998900007777'");
        foreach (['TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID', 'APP_URL'] as $key) {
            unset($_ENV[$key]);
        }
    }

    public function testDoesNothingWhenNotConfigured(): void
    {
        $calls = 0;
        $notifier = new TelegramNotifier(send: function () use (&$calls): bool {
            $calls++;
            return true;
        });

        $notifier->notifyNewApplication($this->applicationId);

        $this->assertSame(0, $calls);
    }

    public function testSendsEscapedApplicationDetailsToConfiguredChat(): void
    {
        $_ENV['TELEGRAM_BOT_TOKEN'] = '123:abc';
        $_ENV['TELEGRAM_CHAT_ID'] = '-100500';
        $_ENV['APP_URL'] = 'https://example.test/';
        $sent = [];
        $notifier = new TelegramNotifier(send: function (string $url, array $payload) use (&$sent): bool {
            $sent[] = [$url, $payload];
            return true;
        });

        $notifier->notifyNewApplication($this->applicationId);

        $this->assertCount(1, $sent);
        [$url, $payload] = $sent[0];
        $this->assertSame('https://api.telegram.org/bot123:abc/sendMessage', $url);
        $this->assertSame('-100500', $payload['chat_id']);
        $this->assertStringContainsString('Ali &lt;Valiyev&gt;', $payload['text']);
        $this->assertStringContainsString('+998900007777', $payload['text']);
        $this->assertStringContainsString("https://example.test/admin/applications/{$this->applicationId}", $payload['text']);
    }

    public function testSendFailureDoesNotThrow(): void
    {
        $_ENV['TELEGRAM_BOT_TOKEN'] = '123:abc';
        $_ENV['TELEGRAM_CHAT_ID'] = '-100500';
        $notifier = new TelegramNotifier(send: function (): bool {
            throw new \RuntimeException('network down');
        });

        $notifier->notifyNewApplication($this->applicationId);

        $this->addToAssertionCount(1);
    }
}
