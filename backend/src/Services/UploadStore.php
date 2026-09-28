<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use GdImage;

/**
 * Stores every uploaded file twice: on disk under public/uploads/ (so the web server
 * can serve it directly) and in the uploaded_files table. The disk copy does not
 * survive a Railway redeploy, the database copy does - public/upload.php serves a
 * missing file from the database and writes it back to disk.
 *
 * Images are re-encoded on the way in: scaled down to $maxSide on the long edge,
 * rotated upright per their EXIF orientation, and saved as WebP. Phone photos of
 * several megabytes come out at a few hundred kilobytes.
 */
final class UploadStore
{
    public const PHOTO_MAX_SIDE = 800;
    public const IMAGE_MAX_SIDE = 1600;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const WEBP_QUALITY = 82;

    private readonly string $publicDir;

    public function __construct(?string $publicDir = null)
    {
        $this->publicDir = $publicDir ?? dirname(__DIR__, 2) . '/public';
    }

    /**
     * Stores $_FILES[$field] as an image under /uploads/$dir. Returns the public URL,
     * or null when nothing was uploaded or the file is not a readable jpg/png/webp image.
     */
    public function storeUploadedImage(string $field, string $dir, string $prefix, int $maxSide = self::IMAGE_MAX_SIDE): ?string
    {
        $file = $this->uploadedFile($field, self::IMAGE_EXTENSIONS);
        if ($file === null) {
            return null;
        }

        $bytes = file_get_contents($file['tmp_name']);
        return $bytes === false ? null : $this->storeImageBytes($bytes, $dir, $prefix, $maxSide);
    }

    /** Stores $_FILES[$field] as a PDF under /uploads/$dir, unchanged. */
    public function storeUploadedPdf(string $field, string $dir, string $prefix): ?string
    {
        $file = $this->uploadedFile($field, ['pdf']);
        if ($file === null) {
            return null;
        }

        $bytes = file_get_contents($file['tmp_name']);
        if ($bytes === false || !str_starts_with($bytes, '%PDF')) {
            return null;
        }

        return $this->put($dir, $prefix, 'pdf', 'application/pdf', $bytes);
    }

    public function storeImageBytes(string $bytes, string $dir, string $prefix, int $maxSide = self::IMAGE_MAX_SIDE): ?string
    {
        $image = @imagecreatefromstring($bytes);
        if (!$image instanceof GdImage) {
            return null;
        }

        $image = $this->orientUpright($image, $bytes);
        $image = $this->scaleDown($image, $maxSide);

        ob_start();
        $encoded = function_exists('imagewebp') && imagewebp($image, null, self::WEBP_QUALITY);
        $output = (string) ob_get_clean();
        if ($encoded) {
            return $this->put($dir, $prefix, 'webp', 'image/webp', $output);
        }

        ob_start();
        imagejpeg($image, null, 85);
        return $this->put($dir, $prefix, 'jpg', 'image/jpeg', (string) ob_get_clean());
    }

    /**
     * Removes an upload from disk and the database. URLs outside /uploads/ (e.g. the
     * bundled /images/... defaults) are left alone.
     */
    public function delete(?string $url): void
    {
        $path = self::normalizePath($url);
        if ($path === null) {
            return;
        }

        $file = $this->publicDir . $path;
        if (is_file($file)) {
            @unlink($file);
        }

        Database::pdo()->prepare('DELETE FROM uploaded_files WHERE path = ?')->execute([$path]);
    }

    /**
     * Deletes the upload only when no record still points at it. Needed for photos,
     * which an approved application and the student it created share by URL.
     */
    public function deleteIfUnused(?string $url): void
    {
        $path = self::normalizePath($url);
        if ($path === null) {
            return;
        }

        $references = [
            'users' => ['photo_url'],
            'applications' => ['photo_url'],
            'teacher_profiles' => ['photo_url'],
            'professions' => ['image_url', 'pdf_url'],
            'news' => ['image_url'],
            'media_items' => ['file_url'],
        ];
        foreach ($references as $table => $columns) {
            foreach ($columns as $column) {
                $stmt = Database::pdo()->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
                $stmt->execute([$path]);
                if ($stmt->fetchColumn() !== false) {
                    return;
                }
            }
        }

        $this->delete($path);
    }

    /**
     * Looks up an upload by its URL path. When found, restores the disk copy (best
     * effort) and returns ['mime' => ..., 'data' => ...].
     */
    public function fetch(string $url): ?array
    {
        $path = self::normalizePath($url);
        if ($path === null) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT mime, data FROM uploaded_files WHERE path = ?');
        $stmt->execute([$path]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $file = $this->publicDir . $path;
        if (!is_file($file)) {
            if (!is_dir(dirname($file))) {
                @mkdir(dirname($file), 0775, true);
            }
            @file_put_contents($file, $row['data']);
        }

        return ['mime' => (string) $row['mime'], 'data' => (string) $row['data']];
    }

    /** Accepts only /uploads/<dir>/<file> with safe characters; anything else is null. */
    public static function normalizePath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);

        return preg_match('#^/uploads/[a-z0-9_-]+/[A-Za-z0-9_.-]+$#', $path) === 1 && !str_contains($path, '..')
            ? $path
            : null;
    }

    /** @return array{tmp_name: string, name: string}|null */
    private function uploadedFile(string $field, array $allowedExtensions): ?array
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true) || !is_uploaded_file((string) $file['tmp_name'])) {
            return null;
        }

        return ['tmp_name' => (string) $file['tmp_name'], 'name' => (string) $file['name']];
    }

    private function put(string $dir, string $prefix, string $extension, string $mime, string $bytes): string
    {
        $path = '/uploads/' . $dir . '/' . $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $extension;

        Database::pdo()
            ->prepare('INSERT INTO uploaded_files (path, mime, data) VALUES (?, ?, ?)')
            ->execute([$path, $mime, $bytes]);

        $file = $this->publicDir . $path;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        file_put_contents($file, $bytes);

        return $path;
    }

    private function scaleDown(GdImage $image, int $maxSide): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = $maxSide / max($width, $height);

        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if ($scale >= 1) {
            return $image;
        }

        $scaled = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);
        if (!$scaled instanceof GdImage) {
            return $image;
        }
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        return $scaled;
    }

    /** Phone cameras store sideways pixels plus an EXIF rotation flag; bake the rotation in. */
    private function orientUpright(GdImage $image, string $bytes): GdImage
    {
        $rotated = match (self::jpegOrientation($bytes)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    /**
     * Reads the EXIF Orientation tag (1-8) from JPEG bytes without the exif extension,
     * which the Docker image does not install. Returns 1 (upright) when absent.
     */
    public static function jpegOrientation(string $bytes): int
    {
        if (!str_starts_with($bytes, "\xFF\xD8")) {
            return 1;
        }

        $offset = 2;
        $length = strlen($bytes);
        while ($offset + 4 <= $length && $bytes[$offset] === "\xFF") {
            $marker = ord($bytes[$offset + 1]);
            $segmentLength = unpack('n', substr($bytes, $offset + 2, 2))[1];

            if ($marker === 0xE1 && substr($bytes, $offset + 4, 6) === "Exif\0\0") {
                $tiff = substr($bytes, $offset + 10, $segmentLength - 8);
                $littleEndian = str_starts_with($tiff, 'II');
                $u16 = static fn (int $at): int => unpack($littleEndian ? 'v' : 'n', substr($tiff, $at, 2))[1] ?? 0;
                $u32 = static fn (int $at): int => unpack($littleEndian ? 'V' : 'N', substr($tiff, $at, 4))[1] ?? 0;
                if (strlen($tiff) < 8) {
                    return 1;
                }

                $ifd = $u32(4);
                $entries = $ifd + 2 <= strlen($tiff) ? $u16($ifd) : 0;
                for ($i = 0; $i < $entries; $i++) {
                    $entry = $ifd + 2 + $i * 12;
                    if ($entry + 12 > strlen($tiff)) {
                        break;
                    }
                    if ($u16($entry) === 0x0112) {
                        $orientation = $u16($entry + 8);
                        return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
                    }
                }
                return 1;
            }

            if ($marker === 0xDA) {
                break;
            }
            $offset += 2 + $segmentLength;
        }

        return 1;
    }
}
