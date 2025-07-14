<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Generate a PDF report with dynamic table data.
 *
 * @param string $title       Report title.
 * @param array<int, array{urun: string, adet: int}> $tableData Table rows.
 * @param string $outputFile  Output file path.
 *
 * @return bool True on success, false on failure.
 */
function generateReportPDF(string $title, array $tableData, string $outputFile): bool
{
    try {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetHTMLHeader('<div style="text-align:center;">GST Projesi Raporu</div>');
        $mpdf->SetHTMLFooter('<div style="text-align:right;">{PAGENO}</div>');

        $html = '<h1>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
        $html .= '<thead><tr><th>Ürün</th><th>Adet</th></tr></thead><tbody>';

        foreach ($tableData as $row) {
            $urun = htmlspecialchars((string)($row['urun'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $adet = (int)($row['adet'] ?? 0);
            $html .= '<tr><td>' . $urun . '</td><td style="text-align:right;">' . $adet . '</td></tr>';
        }

        $html .= '</tbody></table>';

        $mpdf->WriteHTML($html);
        $mpdf->Output($outputFile, Destination::FILE);
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}
?>
