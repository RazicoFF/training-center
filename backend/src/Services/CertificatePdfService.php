<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;

final class CertificatePdfService
{
    public function generate(array $data): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/certificates';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $html = sprintf(
            '<h1>Sertifikat</h1><p>%s</p><p>%s</p><p>%s</p><p>%s</p>',
            htmlspecialchars($data['full_name']),
            htmlspecialchars($data['profession_name']),
            htmlspecialchars($data['issue_date']),
            htmlspecialchars($data['certificate_number'])
        );

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        $path = $dir . '/' . $data['certificate_number'] . '.pdf';
        file_put_contents($path, $dompdf->output());

        return $path;
    }
}
