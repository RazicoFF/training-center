<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Repositories\ApplicationRepository;

/**
 * Sends a Telegram message to the admins' chat when a new application arrives, so
 * nobody has to keep refreshing the admin panel. Configured with TELEGRAM_BOT_TOKEN
 * and TELEGRAM_CHAT_ID (APP_URL adds a link to the application card); without them
 * it does nothing. A failed send is logged and never blocks the applicant.
 */
final class TelegramNotifier
{
    /** @var callable(string $url, array $payload): bool */
    private $send;

    public function __construct(
        private readonly ApplicationRepository $applications = new ApplicationRepository(),
        ?callable $send = null
    ) {
        $this->send = $send ?? self::httpPost(...);
    }

    public function isConfigured(): bool
    {
        return (string) Env::get('TELEGRAM_BOT_TOKEN', '') !== '' && (string) Env::get('TELEGRAM_CHAT_ID', '') !== '';
    }

    public function notifyNewApplication(int $applicationId): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $application = $this->applications->findWithProfession($applicationId);
        if ($application === null) {
            return;
        }

        try {
            ($this->send)(
                'https://api.telegram.org/bot' . Env::get('TELEGRAM_BOT_TOKEN') . '/sendMessage',
                [
                    'chat_id' => Env::get('TELEGRAM_CHAT_ID'),
                    'text' => $this->formatApplication($application),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );
        } catch (\Throwable $e) {
            error_log('Telegram notification failed: ' . $e->getMessage());
        }
    }

    public function formatApplication(array $application): string
    {
        $lines = [
            '🆕 <b>Yangi ariza</b>',
            '',
            '👤 ' . htmlspecialchars((string) $application['full_name']),
            '📞 ' . htmlspecialchars((string) $application['phone']),
            '🛠 ' . htmlspecialchars((string) $application['profession_name_uz']),
        ];
        if (!empty($application['brand_name'])) {
            $lines[] = '🚜 ' . htmlspecialchars((string) $application['brand_name']);
        }

        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        if ($appUrl !== '') {
            $lines[] = '';
            $lines[] = '<a href="' . htmlspecialchars($appUrl . '/admin/applications/' . (int) $application['id']) . '">Admin panelda ochish</a>';
        }

        return implode("\n", $lines);
    }

    private static function httpPost(string $url, array $payload): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $ok = $response !== false && (json_decode($response, true)['ok'] ?? false) === true;
        if (!$ok) {
            error_log('Telegram sendMessage failed: ' . ($response === false ? 'no response' : $response));
        }

        return $ok;
    }
}
