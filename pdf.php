<?php
require_once 'config.php';
define('FPDF_FONTPATH', __DIR__ . '/font/');
require_once 'tFPDF.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$quote_id) {
    die('Teklif ID gerekli');
}

$stmt = $pdo->prepare("SELECT mq.*, co.name AS company_name, CONCAT(cu.first_name,' ',cu.last_name) AS customer_name
                        FROM master_quotes mq
                        LEFT JOIN companies co ON mq.company_id = co.id
                        LEFT JOIN customers cu ON mq.contact_id = cu.id
                        WHERE mq.id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();
if (!$quote) {
    die('Teklif bulunamadı');
}

$gStmt = $pdo->prepare("SELECT * FROM guillotine_quotes WHERE master_quote_id=?");
$gStmt->execute([$quote_id]);
$guillotines = $gStmt->fetchAll();

$sStmt = $pdo->prepare("SELECT * FROM sliding_quotes WHERE master_quote_id=?");
$sStmt->execute([$quote_id]);
$slidings = $sStmt->fetchAll();

function compute_optimization(PDO $pdo, float $width, float $height, int $quantity, string $glass_type, float $profit_margin = 0)
{
    $motor_kutusu = $width - 14;
    $motor_kapak = $motor_kutusu - 1;
    $alt_kasa = $width;
    $tutamak = $width - 185;
    $kenetli_baza = $width - 185;
    $kupeste_baza = $width - 185;
    $kupeste = $width - 185;
    $dikey_baza = ($height - 290) / 3;
    $kanat = $dikey_baza;
    $dikme = $height - 166;
    $orta_dikme = $dikme;
    $son_kapatma = $height - $kanat - 221;
    $yatak_citasi = $kenetli_baza - 52;
    $dikey_citasi = $dikey_baza - 5;
    $zincir = $son_kapatma + 600;
    $flatbelt_kayis = $son_kapatma + 600;
    $motor_borusu = $width - 59;

    $cam_en = $width - 221;
    $cam_boy = $dikey_baza + 26;

    $motor_kutusu_qty = $quantity;
    $motor_kapak_qty = $quantity;
    $alt_kasa_qty = $quantity;
    $kenetli_baza_qty = 2 * $quantity;
    $kupeste_baza_qty = 2 * $quantity;
    $tutamak_qty = 6 * $quantity - $kenetli_baza_qty - $kupeste_baza_qty;
    $kupeste_qty = $quantity;
    if (strtolower($glass_type) === 'tek cam') {
        $yatay_citasi_qty = 6 * $quantity;
        $dikey_citasi_qty = 6 * $quantity;
    } else {
        $yatay_citasi_qty = 0;
        $dikey_citasi_qty = 0;
    }
    $dikme_qty = 2 * $quantity;
    $orta_dikme_qty = 2 * $quantity;
    $son_kapatma_qty = 2 * $quantity;
    $kanat_qty = 2 * $quantity;
    $dikey_baza_qty = 4 * $quantity;
    $zincir_qty = 2 * $quantity;
    $motor_borusu_qty = $quantity;
    $motor_kutu_contasi = (($motor_kutusu * $quantity) + ($alt_kasa * $quantity)) / 1000;
    $kanat_contasi = $kanat * $quantity * 2 / 1000;
    $kenet_fitili = (($tutamak * $quantity) + ($kenetli_baza * $quantity)) / 1000;
    $kil_fitil = (($dikme * $quantity) + ($orta_dikme * $quantity * 2) + ($son_kapatma * $quantity) + ($kanat * $quantity)) / 1000;

    $cam_adet = ($kanat_qty + $dikey_baza_qty) / 2;

    $results = [
        ['name' => 'Motor Kutusu', 'length' => $motor_kutusu, 'count' => $motor_kutusu_qty],
        ['name' => 'Motor Kapak', 'length' => $motor_kapak, 'count' => $motor_kapak_qty],
        ['name' => 'Alt Kasa', 'length' => $alt_kasa, 'count' => $alt_kasa_qty],
        ['name' => 'Tutamak', 'length' => $tutamak, 'count' => $tutamak_qty],
        ['name' => 'Kenetli Baza', 'length' => $kenetli_baza, 'count' => $kenetli_baza_qty],
        ['name' => 'Küpeşte Baza', 'length' => $kupeste_baza, 'count' => $kupeste_baza_qty],
        ['name' => 'Küpeşte', 'length' => $kupeste, 'count' => $kupeste_qty],
        ['name' => 'Yatay Tek Cam Çıtası', 'length' => $yatak_citasi, 'count' => $yatay_citasi_qty],
        ['name' => 'Dikey Tek Cam Çıtası', 'length' => $dikey_citasi, 'count' => $dikey_citasi_qty],
        ['name' => 'Dikme', 'length' => $dikme, 'count' => $dikme_qty],
        ['name' => 'Orta Dikme', 'length' => $orta_dikme, 'count' => $orta_dikme_qty],
        ['name' => 'Son Kapatma', 'length' => $son_kapatma, 'count' => $son_kapatma_qty],
        ['name' => 'Kanat', 'length' => $kanat, 'count' => $kanat_qty],
        ['name' => 'Dikey Baza', 'length' => $dikey_baza, 'count' => $dikey_baza_qty],
        ['name' => 'Cam', 'length' => $cam_en . ' x ' . $cam_boy, 'count' => $cam_adet],
        ['name' => 'Zincir', 'length' => $zincir, 'count' => $zincir_qty],
        ['name' => 'Flatbelt Kayış', 'length' => $flatbelt_kayis, 'count' => '-'],
        ['name' => 'Motor Borusu', 'length' => $motor_borusu, 'count' => $motor_borusu_qty],
        ['name' => 'Motor Kutu Contası (m)', 'length' => $motor_kutu_contasi, 'count' => '-'],
        ['name' => 'Kanat Contası (m)', 'length' => $kanat_contasi, 'count' => '-'],
        ['name' => 'Kenet Fitili (m)', 'length' => $kenet_fitili, 'count' => '-'],
        ['name' => 'Kıl Fitil (m)', 'length' => $kil_fitil, 'count' => '-'],
    ];

    $nameCategoryMap = [
        'Motor Kutusu' => 'Alüminyum',
        'Motor Kapak' => 'Alüminyum',
        'Alt Kasa' => 'Alüminyum',
        'Tutamak' => 'Alüminyum',
        'Kenetli Baza' => 'Alüminyum',
        'Küpeşte Baza' => 'Alüminyum',
        'Küpeşte' => 'Alüminyum',
        'Yatay Tek Cam Çıtası' => 'Alüminyum',
        'Dikey Tek Cam Çıtası' => 'Alüminyum',
        'Dikme' => 'Alüminyum',
        'Orta Dikme' => 'Alüminyum',
        'Son Kapatma' => 'Alüminyum',
        'Kanat' => 'Alüminyum',
        'Dikey Baza' => 'Alüminyum',
        'Motor Borusu' => 'Alüminyum',
        'Cam' => 'Cam',
        'Zincir' => 'Aksesuar',
        'Flatbelt Kayış' => 'Aksesuar',
        'Motor Kutu Contası (m)' => 'Fitil',
        'Kanat Contası (m)' => 'Fitil',
        'Kenet Fitili (m)' => 'Fitil',
        'Kıl Fitil (m)' => 'Fitil',
    ];

    $total_cost = 0;
    foreach ($results as &$row) {
        $stmt = $pdo->prepare('SELECT unit, measure_value, unit_price, category FROM products WHERE name = ? LIMIT 1');
        $stmt->execute([$row['name']]);
        $product = $stmt->fetch();
        $row['cost'] = null;
        $row['category'] = $nameCategoryMap[$row['name']] ?? ($product['category'] ?? 'Diğer');
        if ($product) {
            $count = is_numeric($row['count']) ? (float) $row['count'] : 0;
            $length = is_numeric($row['length']) ? (float) $row['length'] : 0;
            if (strpos($row['name'], '(m)') === false && is_numeric($row['length'])) {
                $length = $row['length'] / 1000; // convert mm to m
            }
            switch (strtolower($product['unit'])) {
                case 'adet':
                    $row['cost'] = $count * $product['unit_price'];
                    break;
                case 'kg':
                    $weight = $length * $product['measure_value'];
                    $row['cost'] = $weight * $count * $product['unit_price'];
                    break;
                default: // metre
                    $row['cost'] = $length * $count * $product['unit_price'];
                    break;
            }
            $total_cost += $row['cost'];
        }
    }
    unset($row);
    $groupedResults = [];
    foreach ($results as $r) {
        $groupedResults[$r['category']][] = $r;
    }
    $sales_price = $total_cost * (1 + $profit_margin / 100);
    return ['results' => $results, 'grouped' => $groupedResults, 'total' => $total_cost, 'sales' => $sales_price];
}

$pdf = new tFPDF();
$pdf->AddPage();
$pdf->AddFont('DejaVu','','DejaVuSans.ttf', true);
$pdf->SetFont('DejaVu','B',14);
$pdf->Cell(0,10,'Teklif Bilgileri',0,1,'C');
$pdf->SetFont('DejaVu','',12);
$pdf->Cell(0,8,'Firma: ' . $quote['company_name'],0,1);
$pdf->Cell(0,8,'Musteri: ' . $quote['customer_name'],0,1);
$pdf->Cell(0,8,'Tarih: ' . $quote['quote_date'],0,1);
$pdf->Ln(4);

foreach ($guillotines as $g) {
    $pdf->SetFont('DejaVu','B',12);
    $pdf->Cell(0,8,'Giyotin Sistem (' . $g['width_mm'] . ' x ' . $g['height_mm'] . ' mm)',0,1);
    $pdf->SetFont('DejaVu','',11);
    $pdf->Cell(0,6,'Adet: ' . $g['system_qty'] . ' | Cam: ' . $g['glass_type'],0,1);
    $opt = compute_optimization($pdo, (float)$g['width_mm'], (float)$g['height_mm'], (int)$g['system_qty'], (string)$g['glass_type']);
    foreach ($opt['grouped'] as $cat => $rows) {
        $pdf->SetFont('DejaVu','B',11);
        $pdf->Cell(0,7,$cat,0,1);
        $pdf->SetFont('DejaVu','B',10);
        $pdf->Cell(80,6,'Parca',1);
        $pdf->Cell(30,6,'Uzunluk',1);
        $pdf->Cell(20,6,'Adet',1);
        $pdf->Cell(30,6,'Maliyet',1,1);
        $pdf->SetFont('DejaVu','',10);
        foreach ($rows as $row) {
            $len = is_numeric($row['length']) ? round($row['length']) : $row['length'];
            $cost = is_null($row['cost']) ? '-' : round($row['cost']);
            $pdf->Cell(80,6,$row['name'],1);
            $pdf->Cell(30,6,$len,1);
            $pdf->Cell(20,6,$row['count'],1);
            $pdf->Cell(30,6,$cost,1,1);
        }
        $pdf->Ln(2);
    }
    $pdf->Cell(130,6,'Toplam Maliyet',1);
    $pdf->Cell(30,6,round($opt['total']),1,1,'R');
    $pdf->Cell(130,6,'Toplam Fiyat',1);
    $pdf->Cell(30,6,round($opt['sales']),1,1,'R');
    $pdf->Ln(5);
}

if ($slidings) {
    $pdf->SetFont('DejaVu','B',12);
    $pdf->Cell(0,8,'Surme Sistemler',0,1);
    $pdf->SetFont('DejaVu','B',10);
    $pdf->Cell(40,6,'Sistem Tipi',1);
    $pdf->Cell(30,6,'En',1);
    $pdf->Cell(30,6,'Boy',1);
    $pdf->Cell(20,6,'Adet',1);
    $pdf->Cell(40,6,'Renk',1,1);
    $pdf->SetFont('DejaVu','',10);
    foreach ($slidings as $s) {
        $pdf->Cell(40,6,$s['system_type'],1);
        $pdf->Cell(30,6,$s['width_mm'],1);
        $pdf->Cell(30,6,$s['height_mm'],1);
        $pdf->Cell(20,6,$s['system_qty'],1);
        $pdf->Cell(40,6,$s['ral_code'],1,1);
    }
    $pdf->Ln(5);
}

$pdf->Output('I','teklif.pdf');
