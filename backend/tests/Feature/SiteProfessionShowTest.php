<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\ProfessionController;
use App\Repositories\ProfessionVideoRepository;
use PHPUnit\Framework\TestCase;

final class SiteProfessionShowTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM profession_videos WHERE profession_id = {$this->professionId}");
    }

    public function testShowRendersDescriptionPdfLinkAndVideoEmbed(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE professions SET pdf_url = ? WHERE id = ?')
            ->execute(['/uploads/professions/sample.pdf', $this->professionId]);
        $pdo->prepare('INSERT INTO profession_videos (profession_id, youtube_url, title_uz) VALUES (?, ?, ?)')
            ->execute([$this->professionId, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Tanishtiruv video']);

        $router = new Router();
        (new ProfessionController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', "/professions/{$this->professionId}", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('/uploads/professions/sample.pdf', $html);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('Tanishtiruv video', $html);

        $pdo->exec("UPDATE professions SET pdf_url = NULL WHERE id = {$this->professionId}");
    }

    public function testShowReturns404ForUnknownProfession(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/professions/999999', [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testYoutubeIdExtractionHandlesCommonUrlShapes(): void
    {
        $this->assertSame('dQw4w9WgXcQ', ProfessionVideoRepository::extractYoutubeId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', ProfessionVideoRepository::extractYoutubeId('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(ProfessionVideoRepository::extractYoutubeId('https://example.com/not-a-video'));
    }
}
