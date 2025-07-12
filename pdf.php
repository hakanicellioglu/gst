<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function generate_pdf(string $title, array $columns, array $rows): string
{
    $y = 800;
    $content = "BT\n/F1 16 Tf\n50 {$y} Td\n(" . pdf_escape($title) . ") Tj\nET\n";
    $y -= 20;
    $content .= "BT\n/F1 12 Tf\n50 {$y} Td\n(" . pdf_escape(implode(' | ', $columns)) . ") Tj\nET\n";
    foreach ($rows as $row) {
        $y -= 15;
        $line = implode(' | ', array_map('strval', $row));
        $content .= "BT\n/F1 12 Tf\n50 {$y} Td\n(" . pdf_escape($line) . ") Tj\nET\n";
    }

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
    $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }
    $xrefStart = strlen($pdf);
    $pdf .= "xref\n0 " . count($offsets) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < count($offsets); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . count($offsets) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefStart}\n%%EOF";

    return $pdf;
}

$type = $_GET['type'] ?? '';
$title = '';
$columns = [];
$rows = [];

switch ($type) {
    case 'company':
        $title = 'Firmalar';
        $columns = ['Ad', 'Telefon', 'Adres', 'E-posta'];
        $stmt = $pdo->query("SELECT name, phone, address, email FROM companies ORDER BY name");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;
    case 'customers':
        $title = 'Müşteriler';
        $columns = ['Firma', 'İsim', 'Soyisim', 'Ünvan', 'E-posta', 'Telefon'];
        $stmt = $pdo->query("SELECT co.name, cu.first_name, cu.last_name, cu.title, cu.email, cu.phone FROM customers cu LEFT JOIN companies co ON cu.company_id = co.id ORDER BY cu.first_name, cu.last_name");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;
    case 'products':
        $title = 'Ürünler';
        $columns = ['Ad', 'Kod', 'Birim', 'Değer', 'Fiyat', 'Kategori'];
        $stmt = $pdo->query("SELECT name, code, unit, measure_value, unit_price, category FROM products ORDER BY name");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;
    case 'offer':
        $title = 'Teklifler';
        $columns = ['Firma', 'Müşteri', 'Tarih', 'Teslimat', 'Ödeme', 'Süre'];
        $stmt = $pdo->query("SELECT co.name, CONCAT(cu.first_name,' ',cu.last_name), mq.quote_date, mq.delivery_term, mq.payment_method, mq.payment_due FROM master_quotes mq LEFT JOIN companies co ON mq.company_id=co.id LEFT JOIN customers cu ON mq.contact_id=cu.id ORDER BY mq.quote_date");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;
    default:
        http_response_code(400);
        echo 'Invalid type';
        exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $type . '.pdf"');
echo generate_pdf($title, $columns, $rows);

