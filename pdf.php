<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$company        = $_POST['company']        ?? $_GET['company']        ?? '';
$contact        = $_POST['contact']        ?? $_GET['contact']        ?? '';
$contactEmail   = $_POST['contact_email']  ?? $_GET['contact_email']  ?? '';
$contactPhone   = $_POST['contact_phone']  ?? $_GET['contact_phone']  ?? '';
$offerDate   = $_POST['offer_date']   ?? $_GET['offer_date']   ?? date('d.m.Y');
$offerNumber = $_POST['offer_number'] ?? $_GET['offer_number'] ?? '';
$delivery    = $_POST['delivery']     ?? $_GET['delivery']     ?? '';
$payment     = $_POST['payment']      ?? $_GET['payment']      ?? '';
$validity    = $_POST['validity']     ?? $_GET['validity']     ?? '';
$preparedBy  = $_POST['prepared_by']  ?? $_GET['prepared_by']  ?? (($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''));
$products = [];
$quoteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($quoteId) {
    $stmt = $pdo->prepare(
        'SELECT mq.*, co.name AS company_name, ' .
        'CONCAT(cu.first_name, " ", cu.last_name) AS contact_name, ' .
        'cu.email AS contact_email, cu.phone AS contact_phone ' .
        'FROM master_quotes mq ' .
        'LEFT JOIN companies co ON mq.company_id = co.id ' .
        'LEFT JOIN customers cu ON mq.contact_id = cu.id ' .
        'WHERE mq.id = ?'
    );
    $stmt->execute([$quoteId]);
    if ($row = $stmt->fetch()) {
        $company      = $row['company_name'];
        $contact      = $row['contact_name'];
        $contactEmail = $row['contact_email'] ?? '';
        $contactPhone = $row['contact_phone'] ?? '';
        $offerDate    = date('d.m.Y', strtotime($row['quote_date']));
        $offerNumber  = 'TKF-' . $row['id'];
        $delivery     = $row['delivery_term'];
        $payment      = $row['payment_method'];
        $validity     = $row['quote_validity'];
    }

    $gStmt = $pdo->prepare(
        'SELECT ral_code, glass_color, system_type, system_qty, width_mm, height_mm '
        . 'FROM guillotine_quotes WHERE master_quote_id=?'
    );
    $gStmt->execute([$quoteId]);
    foreach ($gStmt as $p) {
        $products[] = [
            'ral'    => $p['ral_code'],
            'glass'  => $p['glass_color'],
            'type'   => $p['system_type'],
            'qty'    => $p['system_qty'],
            'width'  => $p['width_mm'],
            'height' => $p['height_mm'],
            'unit'   => 0,
        ];
    }

    $sStmt = $pdo->prepare(
        'SELECT ral_code, glass_color, system_type, system_qty, width_mm, height_mm '
        . 'FROM sliding_quotes WHERE master_quote_id=?'
    );
    $sStmt->execute([$quoteId]);
    foreach ($sStmt as $p) {
        $products[] = [
            'ral'    => $p['ral_code'],
            'glass'  => $p['glass_color'],
            'type'   => $p['system_type'],
            'qty'    => $p['system_qty'],
            'width'  => $p['width_mm'],
            'height' => $p['height_mm'],
            'unit'   => 0,
        ];
    }
}

if (empty($products)) {
    $products = $_POST['products'] ?? $_GET['products'] ?? [];
    if (!is_array($products)) {
        $products = [];
    }
    if (empty($offerNumber)) {
        $offerNumber = 'TKF-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

$bankAccounts = $_POST['bank_accounts'] ?? $_GET['bank_accounts'] ?? [];
if (!$bankAccounts) {
    $bankAccounts = [
        [
            'bank'    => 'ALBARAKA TÜRK KATILIM BANKASI',
            'company' => 'ALUMANN ALÜMİNYUM SANAYİ VE TİCARET A.Ş.',
            'iban'    => 'TR33 0020 3000 0956 2368 0000 01',
        ],
        [
            'bank'    => 'VAKIF KATILIM BANKASI',
            'company' => 'ALUMANN ALÜMİNYUM SANAYİ VE TİCARET A.Ş.',
            'iban'    => 'TR55 0021 0000 0008 3591 5000 01',
        ],
        [
            'bank'    => 'VAKIF BANK',
            'company' => 'ALUMANN ALÜMİNYUM SANAYİ VE TİCARET A.Ş.',
            'iban'    => 'TR44 0001 5001 5800 7321 3983 24',
        ],
    ];
}

$subtotal = 0;
foreach ($products as &$p) {
    $p['meterage']       = (($p['width'] + $p['height']) * 2) / 1000;
    $p['total_meterage'] = $p['meterage'] * $p['qty'];
    $p['total_price']    = $p['total_meterage'] * $p['unit'];
    $subtotal += $p['total_price'];
}
unset($p);
$vat   = $subtotal * 0.20;
$grand = $subtotal + $vat;
$mpdf = new Mpdf(['format' => 'A4']);
ob_start();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Teklif Formu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <style>
    @page {
        size: A4;
        margin: 5mm;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
    }

    .pdf-container {
        width: 190mm;
        margin: auto;
    }

    .quote-header img {
        max-height: 60px;
    }

    .page-break {
        page-break-before: always;
    }

    h1 {
        text-align: center;
        color: #c00;
        margin-top: 0;
    }

    table {
        width: 100%;
        margin-top: 10px;
        border-collapse: collapse;
    }

    .table-bordered td,
    .table-bordered th {
        border: 1px solid #000 !important;
    }

    .signature td {
        height: 60px;
        text-align: center;
        border: none;
    }

    .bank-accounts td {
        text-align: center;
        vertical-align: top;
    }

    @media print {
        tr {
            page-break-inside: avoid;
        }

        table,
        thead,
        tbody,
        th,
        td,
        tr {
            page-break-inside: avoid !important;
            page-break-after: auto;
        }

        thead {
            display: table-row-group;
        }

        .container {
            margin: 5svh;
        }

        .no-print {
            display: none;
        }

        #print-container,
        #print-container * {
            display: none !important;
        }

        .page-break {
            page-break-before: always;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <header class="quote-header text-center mb-3">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/5+BFwADhgHqvh2xAAAAAElFTkSuQmCC"
                alt="Logo" class="mb-2" />
            <h1 class="text-danger">DEMONTE TEKLİF FORMU</h1>

            <div class="row justify-content-center">
                <!-- Sol Sütun -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4 fw-bold">Firma:</div>
                        <div class="col-8"><?php echo htmlspecialchars($company); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Sayın:</div>
                        <div class="col-8"><?php echo htmlspecialchars($contact); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Telefon:</div>
                        <div class="col-8"><?php echo htmlspecialchars($contactPhone); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">E-posta:</div>
                        <div class="col-8"><?php echo htmlspecialchars($contactEmail); ?></div>
                    </div>
                </div>

                <!-- Sağ Sütun -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4 fw-bold">Teklif No:</div>
                        <div class="col-8"><?php echo $offerNumber; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Teklif Tarihi:</div>
                        <div class="col-8"><?php echo $offerDate; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Hazırlayan:</div>
                        <div class="col-8"><?php echo htmlspecialchars($preparedBy); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">E-posta:</div>
                        <div class="col-8">siparis@alumann.com</div>
                    </div>
                </div>
            </div>
        </header>




        <table class="table table-bordered table-sm">
            <thead class="table-dark">
                <tr>
                    <th class="text-center py-3">RAL Kodu</th>
                    <th class="text-center py-3">Cam Rengi</th>
                    <th class="text-center py-3">Sistem Tipi</th>
                    <th class="text-center py-3">Adet</th>
                    <th class="text-center py-3">En (mm)</th>
                    <th class="text-center py-3">Boy (mm)</th>
                    <th class="text-center py-3">Metraj (m)</th>
                    <th class="text-center py-3">Toplam Metraj (m)</th>
                    <th class="text-center py-3">Toplam Fiyat ₺</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td class="text-center py-3"><?=htmlspecialchars($p['ral'])?></td>
                    <td class="text-center py-3"><?=htmlspecialchars($p['glass'])?></td>
                    <td class="text-center py-3"><?=htmlspecialchars($p['type'])?></td>
                    <td class="text-center py-3"><?=$p['qty']?></td>
                    <td class="text-center py-3"><?=$p['width']?></td>
                    <td class="text-center py-3"><?=$p['height']?></td>
                    <td class="text-center py-3"><?=number_format($p['meterage'], 2)?></td>
                    <td class="text-center py-3"><?=number_format($p['total_meterage'], 2)?></td>
                    <td class="text-center py-3"><?=number_format($p['total_price'], 2)?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8">Ara Toplam</td>
                    <td><?=number_format($subtotal, 2)?> ₺</td>
                </tr>
                <tr>
                    <td colspan="8">KDV %20</td>
                    <td><?=number_format($vat, 2)?> ₺</td>
                </tr>
                <tr>
                    <td colspan="8"><strong>Genel Toplam</strong></td>
                    <td><strong><?=number_format($grand, 2)?> ₺</strong></td>
                </tr>
            </tfoot>
        </table>

        <p><strong>Teslimat:</strong> <?=$delivery?></p>
        <p><strong>Ödeme:</strong> <?=$payment?></p>
        <p><strong>Teklif Geçerlilik:</strong> <?=$validity?></p>

        <div class="page-break"></div>

        <table class="table table-bordered table-sm mt-3">
            <thead class="table-dark">
                <tr>
                    <th class="text-center py-3">Açıklamalar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ÖDEMESİ YAPILMAMIŞ SİPARİŞLER, SEVK TARİHİNDEKİ FİYATTAN FATURA EDİLİR.</td>
                </tr>
                <tr>
                    <td>VADELİ ÖDEMELERDE %5 FİYAT FARKI EKLENECEKTİR.</td>
                </tr>
                <tr>
                    <td>SİPARİŞLER MÜŞTERİ TARAFINDAN KONTROL EDİLİP ONAYLANDIKTAN SONRA PLANLAMAYA ALINIR.</td>
                </tr>
                <tr>
                    <td>ÖZEL BOY TÜM ÜRÜNLERDE ±%10 ÜRETİLEBİLİR. BU DURUMDA ÜRETİLEN MAL MÜŞTERİYE SEVK EDİLİR.</td>
                </tr>
                <tr>
                    <td>SİPARİŞLERDE NAKLİYE ÜCRETİ MÜŞTERİYE AİTTİR.</td>
                </tr>
            </tbody>
        </table>

        <table class="table table-bordered table-sm bank-accounts">
            <thead class="table-dark">
                <tr>
                    <th colspan="3" class="text-center py-3">Banka Bilgileri</th>
                </tr>
                <tr>
                    <th class="py-3 text-center">Banka Adı</th>
                    <th class="py-3 text-center">Firma Adı</th>
                    <th class="py-3 text-center">IBAN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bankAccounts as $b):
                $bankName = $b['bank'] ?? null;
                $companyName = $b['company'] ?? null;
                if (!$bankName || !$companyName) {
                    $parts = preg_split('/\s*[–-]\s*/u', $b['name'] ?? '', 2);
                    $bankName = $bankName ?: ($parts[0] ?? '');
                    $companyName = $companyName ?: ($parts[1] ?? '');
                }
            ?>
                <tr>
                    <td style="font-size: 10px"><?=htmlspecialchars($bankName)?></td>
                    <td style="font-size: 10px"><?=htmlspecialchars($companyName)?></td>
                    <td style="font-size: 10px"><?=htmlspecialchars($b['iban'] ?? '')?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered table-sm signature mt-4 w-100">
            <tr>
                <td>Teklif Onayı</td>
                <td>Müşteri Onayı</td>
            </tr>
            <tr>
                <td class="py-4"></td>
                <td class="py-4"></td>
            </tr>
        </table>


    </div>
</body>
</html>
<?php
$html = ob_get_clean();
$mpdf->WriteHTML($html);
$mpdf->Output('teklif.pdf', 'I');
?>