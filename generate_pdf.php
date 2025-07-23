<?php
/**
 * PDF generating script using mPDF
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

ob_start();
include __DIR__ . '/pdf.php';
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'dejavusans'
]);
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont  = true;

$footer = '<div style="text-align:center;font-size:9pt">' . date('d.m.Y') .
          ' | Sayfa {PAGENO} / {nbpg}</div>';
$mpdf->SetHTMLFooter($footer);

$headerFirst = '<div style="text-align:right;font-weight:bold;font-size:10pt">'
             . 'Teklif No: ' . htmlspecialchars($offerNumber) . '</div>';

$parts = preg_split('/<div class="page-break"><\/div>/', $html, 2);
$firstPage = $parts[0];
$restPages = $parts[1] ?? '';

$mpdf->SetHTMLHeader($headerFirst);
$mpdf->WriteHTML($firstPage);

if ($restPages !== '') {
    $mpdf->AddPage();
    $mpdf->SetHTMLHeader('');
    $mpdf->WriteHTML($restPages);
}

$filename = 'Teklif_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $offerNumber) . '.pdf';
if (!empty($_GET['save'])) {
    $savePath = __DIR__ . '/' . $filename;
    $mpdf->Output($savePath, \Mpdf\Output\Destination::FILE);
    echo 'PDF kaydedildi: ' . $savePath;
} else {
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
}
?>
