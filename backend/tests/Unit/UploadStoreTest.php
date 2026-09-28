<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\UploadStore;
use PHPUnit\Framework\TestCase;

final class UploadStoreTest extends TestCase
{
    private string $publicDir;
    private UploadStore $store;

    protected function setUp(): void
    {
        $this->publicDir = sys_get_temp_dir() . '/upload-store-test-' . bin2hex(random_bytes(4));
        mkdir($this->publicDir, 0775, true);
        $this->store = new UploadStore($this->publicDir);
    }

    protected function tearDown(): void
    {
        Database::pdo()->exec("DELETE FROM uploaded_files WHERE path LIKE '/uploads/unittest/%'");
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->publicDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->publicDir);
    }

    public function testLargeImageIsScaledDownAndStoredAsWebpOnDiskAndInDatabase(): void
    {
        $url = $this->store->storeImageBytes($this->pngBytes(3000, 2000), 'unittest', 'pic');

        $this->assertNotNull($url);
        $this->assertMatchesRegularExpression('#^/uploads/unittest/pic-[0-9a-f]{16}\.webp$#', $url);

        $size = getimagesize($this->publicDir . $url);
        $this->assertSame([1600, 1067], [$size[0], $size[1]]);
        $this->assertSame('image/webp', $size['mime']);

        $row = Database::pdo()->prepare('SELECT mime, LENGTH(data) AS bytes FROM uploaded_files WHERE path = ?');
        $row->execute([$url]);
        $stored = $row->fetch();
        $this->assertSame('image/webp', $stored['mime']);
        $this->assertSame(filesize($this->publicDir . $url), (int) $stored['bytes']);
    }

    public function testSmallImageKeepsItsSize(): void
    {
        $url = $this->store->storeImageBytes($this->pngBytes(300, 200), 'unittest', 'pic');

        $size = getimagesize($this->publicDir . $url);
        $this->assertSame([300, 200], [$size[0], $size[1]]);
    }

    public function testNonImageBytesAreRejected(): void
    {
        $this->assertNull($this->store->storeImageBytes('<?php echo 1;', 'unittest', 'pic'));
    }

    public function testFetchRestoresAFileMissingFromDisk(): void
    {
        $url = $this->store->storeImageBytes($this->pngBytes(40, 40), 'unittest', 'pic');
        $original = file_get_contents($this->publicDir . $url);
        unlink($this->publicDir . $url);

        $fetched = $this->store->fetch($url);

        $this->assertSame('image/webp', $fetched['mime']);
        $this->assertSame($original, $fetched['data']);
        $this->assertFileExists($this->publicDir . $url);
    }

    public function testDeleteRemovesDiskAndDatabaseCopies(): void
    {
        $url = $this->store->storeImageBytes($this->pngBytes(40, 40), 'unittest', 'pic');

        $this->store->delete($url);

        $this->assertFileDoesNotExist($this->publicDir . $url);
        $this->assertNull($this->store->fetch($url));
    }

    public function testDeleteIgnoresBundledImages(): void
    {
        mkdir($this->publicDir . '/images', 0775, true);
        file_put_contents($this->publicDir . '/images/logo.png', 'x');

        $this->store->delete('/images/logo.png');

        $this->assertFileExists($this->publicDir . '/images/logo.png');
    }

    public function testNormalizePathRejectsAnythingOutsideUploads(): void
    {
        $this->assertSame('/uploads/news/a-1.webp', UploadStore::normalizePath('/uploads/news/a-1.webp?v=2'));
        $this->assertNull(UploadStore::normalizePath('/uploads/../.env'));
        $this->assertNull(UploadStore::normalizePath('/uploads/news/../../.env'));
        $this->assertNull(UploadStore::normalizePath('/images/logo.png'));
        $this->assertNull(UploadStore::normalizePath(null));
    }

    public function testExifOrientationIsReadAndAppliedToJpegs(): void
    {
        $jpeg = $this->jpegWithOrientation(400, 200, 6);
        $this->assertSame(6, UploadStore::jpegOrientation($jpeg));
        $this->assertSame(1, UploadStore::jpegOrientation($this->jpegWithOrientation(10, 10, null)));

        $url = $this->store->storeImageBytes($jpeg, 'unittest', 'pic');

        $size = getimagesize($this->publicDir . $url);
        $this->assertSame([200, 400], [$size[0], $size[1]], 'orientation 6 means rotate 90° clockwise');
    }

    private function pngBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 40));
        ob_start();
        imagepng($image);
        return (string) ob_get_clean();
    }

    private function jpegWithOrientation(int $width, int $height, ?int $orientation): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();
        if ($orientation === null) {
            return $jpeg;
        }

        // Big-endian TIFF header + IFD0 with a single Orientation (0x0112, SHORT) entry.
        $tiff = "MM\x00\x2A" . pack('N', 8) . pack('n', 1)
            . pack('nnN', 0x0112, 3, 1) . pack('nn', $orientation, 0)
            . pack('N', 0);
        $app1 = "Exif\x00\x00" . $tiff;
        $segment = "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1;

        return substr($jpeg, 0, 2) . $segment . substr($jpeg, 2);
    }
}
