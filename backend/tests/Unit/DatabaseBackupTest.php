<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Core\Env;
use App\Services\DatabaseBackup;
use App\Services\UploadStore;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseBackupTest extends TestCase
{
    private const RESTORE_DB = 'tc_backup_restore_check';

    public function testDumpRestoresToAnIdenticalDatabase(): void
    {
        $pdo = Database::pdo();
        $publicDir = sys_get_temp_dir() . '/backup-test-' . bin2hex(random_bytes(4));
        $imageUrl = (new UploadStore($publicDir))->storeImageBytes($this->pngBytes(), 'unittest', 'backup');
        $pdo->exec("DELETE FROM news WHERE title_uz = 'Backup ''quoted'' news'");
        $pdo->prepare('INSERT INTO news (title_uz, title_ru, body_uz) VALUES (?, ?, ?)')
            ->execute(["Backup 'quoted' news", 'Резервная копия', "Line 1\nLine 2 \\ backslash"]);

        $sql = '';
        (new DatabaseBackup())->dump(function (string $chunk) use (&$sql): void {
            $sql .= $chunk;
        });

        $restore = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Env::get('DB_HOST'), Env::get('DB_PORT', '3306')),
            (string) Env::get('DB_USER'),
            (string) Env::get('DB_PASS', ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $restore->exec('DROP DATABASE IF EXISTS ' . self::RESTORE_DB);
        $restore->exec('CREATE DATABASE ' . self::RESTORE_DB . ' CHARACTER SET utf8mb4');
        $restore->exec('USE ' . self::RESTORE_DB);

        try {
            $restore->exec($sql);

            foreach (['professions', 'news', 'uploaded_files', 'users', 'site_settings'] as $table) {
                $this->assertSame(
                    (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(),
                    (int) $restore->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(),
                    "row count of {$table}"
                );
            }

            $blob = $restore->prepare('SELECT data FROM uploaded_files WHERE path = ?');
            $blob->execute([$imageUrl]);
            $original = $pdo->prepare('SELECT data FROM uploaded_files WHERE path = ?');
            $original->execute([$imageUrl]);
            $this->assertSame($original->fetchColumn(), $blob->fetchColumn(), 'image bytes survive the round trip');

            $news = $restore->query("SELECT title_uz, body_uz, published_at FROM news WHERE title_ru = 'Резервная копия'")->fetch(PDO::FETCH_ASSOC);
            $this->assertSame("Backup 'quoted' news", $news['title_uz']);
            $this->assertSame("Line 1\nLine 2 \\ backslash", $news['body_uz']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $news['published_at']);

            $price = $restore->query('SELECT price FROM professions ORDER BY id LIMIT 1')->fetchColumn();
            $this->assertSame($pdo->query('SELECT price FROM professions ORDER BY id LIMIT 1')->fetchColumn(), $price);
        } finally {
            $restore->exec('DROP DATABASE IF EXISTS ' . self::RESTORE_DB);
            $pdo->exec("DELETE FROM news WHERE title_ru = 'Резервная копия'");
            (new UploadStore($publicDir))->delete($imageUrl);
            @rmdir($publicDir . '/uploads/unittest');
            @rmdir($publicDir . '/uploads');
            @rmdir($publicDir);
        }
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(50, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 200, 90));
        ob_start();
        imagepng($image);
        return (string) ob_get_clean();
    }
}
