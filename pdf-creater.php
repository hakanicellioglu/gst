<?php
// Example configuration for including the FPDF library
// Adjust the path according to your project structure
require_once __DIR__ . '/vendor/fpdf/fpdf.php';

// Database connection parameters - update as needed
$host = 'localhost';
$db   = 'teklif';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Retrieve quote id from GET parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Invalid quote id.');
}

// Fetch main quote information with company and customer details
$quoteSql = "SELECT mq.id, mq.quote_date, mq.total_amount,
                    c.name AS company_name, c.address AS company_address, c.phone AS company_phone,
                    cu.first_name, cu.last_name, cu.phone AS customer_phone, cu.address AS customer_address
             FROM master_quotes mq
             JOIN companies c ON mq.company_id = c.id
             JOIN customers cu ON mq.contact_id = cu.id
             WHERE mq.id = :id";
$quoteStmt = $pdo->prepare($quoteSql);
$quoteStmt->execute(['id' => $id]);
$quote = $quoteStmt->fetch();
if (!$quote) {
    die('Quote not found.');
}

// Fetch quote line items from guillotine_quotes table
$itemStmt = $pdo->prepare("SELECT system_type, width_mm, height_mm, system_qty FROM guillotine_quotes WHERE master_quote_id = :id");
$itemStmt->execute(['id' => $id]);
$items = $itemStmt->fetchAll();

// Begin PDF generation
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'TEKLIF', 0, 1, 'C');
$pdf->Ln(10);

// Company information
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 8, 'Firma:');
$pdf->Cell(0, 8, $quote['company_name'], 0, 1);
$pdf->Cell(40, 8, 'Adres:');
$pdf->Cell(0, 8, $quote['company_address'], 0, 1);
$pdf->Cell(40, 8, 'Telefon:');
$pdf->Cell(0, 8, $quote['company_phone'], 0, 1);
$pdf->Ln(5);

// Quote date
$pdf->Cell(40, 8, 'Tarih:');
$pdf->Cell(0, 8, $quote['quote_date'], 0, 1);
$pdf->Ln(5);

// Items table headers
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(70, 8, 'Urun', 1);
$pdf->Cell(30, 8, 'Adet', 1);
$pdf->Cell(40, 8, 'Birim Fiyat', 1);
$pdf->Cell(40, 8, 'Toplam', 1);
$pdf->Ln();

// Item rows
$pdf->SetFont('Arial', '', 12);
foreach ($items as $item) {
    $productName = $item['system_type'] . ' ' . $item['width_mm'] . 'x' . $item['height_mm'];
    $qty = $item['system_qty'];
    // Example placeholders for unit price and total price values
    $unitPrice = '0.00';
    $totalPrice = '0.00';

    $pdf->Cell(70, 8, $productName, 1);
    $pdf->Cell(30, 8, $qty, 1, 0, 'C');
    $pdf->Cell(40, 8, $unitPrice, 1, 0, 'R');
    $pdf->Cell(40, 8, $totalPrice, 1, 0, 'R');
    $pdf->Ln();
}

$pdf->Ln(5);
$pdf->Cell(40, 8, 'Toplam Tutar:');
$pdf->Cell(0, 8, $quote['total_amount'], 0, 1);

// Output the PDF for download
$filename = 'Teklif_' . $id . '.pdf';
$pdf->Output('D', $filename);
