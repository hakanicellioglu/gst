<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$company     = $_POST['company']      ?? $_GET['company']      ?? '';
$contact     = $_POST['contact']      ?? $_GET['contact']      ?? '';
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
        'CONCAT(cu.first_name, " ", cu.last_name) AS contact_name ' .
        'FROM master_quotes mq ' .
        'LEFT JOIN companies co ON mq.company_id = co.id ' .
        'LEFT JOIN customers cu ON mq.contact_id = cu.id ' .
        'WHERE mq.id = ?'
    );
    $stmt->execute([$quoteId]);
    if ($row = $stmt->fetch()) {
        $company     = $row['company_name'];
        $contact     = $row['contact_name'];
        $offerDate   = date('d.m.Y', strtotime($row['quote_date']));
        $offerNumber = 'TKF-' . $row['id'];
        $delivery    = $row['delivery_term'];
        $payment     = $row['payment_method'];
        $validity    = $row['quote_validity'];
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
        .no-print {
            display: none;
        }

        #print-container,
        #print-container * {
            display: none !important;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center text-danger">DEMONTE TEKLİF FORMU</h1>

        <div class="no-print d-flex justify-content-end my-3" id="print-container">
            <button id="print-btn" class="btn btn-primary">Yazdır</button>
        </div>

        <div class="row">
            <!-- Sol Sütun -->
            <div class="col-md-6">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Firma:</div>
                    <div class="col-8"><?php echo htmlspecialchars($company); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Sayın:</div>
                    <div class="col-8"><?php echo htmlspecialchars($contact); ?></div>
                </div>
            </div>

            <!-- Sağ Sütun -->
            <div class="col-md-6">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Teklif No:</div>
                    <div class="col-8"><?php echo $offerNumber; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Teklif Tarihi:</div>
                    <div class="col-8"><?php echo $offerDate; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Hazırlayan:</div>
                    <div class="col-8"><?php echo htmlspecialchars($preparedBy); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">E-posta:</div>
                    <div class="col-8">siparis@alumann.com</div>
                </div>
            </div>
        </div>


        <table class="table table-bordered table-sm">
            <thead class="table-dark">
                <tr>
                    <th>RAL Kodu</th>
                    <th>Cam Rengi</th>
                    <th>Sistem Tipi</th>
                    <th>Adet</th>
                    <th>En (mm)</th>
                    <th>Boy (mm)</th>
                    <th>Metraj (m)</th>
                    <th>Toplam Metraj (m)</th>
                    <th>Toplam Fiyat ₺</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?=htmlspecialchars($p['ral'])?></td>
                    <td><?=htmlspecialchars($p['glass'])?></td>
                    <td><?=htmlspecialchars($p['type'])?></td>
                    <td><?=$p['qty']?></td>
                    <td><?=$p['width']?></td>
                    <td><?=$p['height']?></td>
                    <td><?=number_format($p['meterage'], 2)?></td>
                    <td><?=number_format($p['total_meterage'], 2)?></td>
                    <td><?=number_format($p['total_price'], 2)?></td>
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


        <table class="table table-bordered table-sm bank-accounts">
            <thead class="table-dark">
                <tr>
                    <th colspan="3">Banka Bilgileri</th>
                </tr>
                <tr>
                    <th>Banka Adı</th>
                    <th>Firma Adı</th>
                    <th>IBAN</th>
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
                    <td><?=htmlspecialchars($bankName)?></td>
                    <td><?=htmlspecialchars($companyName)?></td>
                    <td><?=htmlspecialchars($b['iban'] ?? '')?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered table-sm mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Explanations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Unpaid orders will be invoiced based on the price valid on the shipment date. A 5% price difference will be added for deferred payments. Orders will only be scheduled after being checked and approved by the customer. For all custom-sized products, a production tolerance of ±10% applies, and the goods produced under this condition will be delivered to the customer. Shipping costs belong to the customer.</td>
                </tr>
            </tbody>
        </table>

        <table class="table table-bordered table-sm signature mt-4 w-100">
            <tr>
                <td>Teklif Onayı</td>
                <td>Müşteri Onayı</td>
            </tr>
            <tr>
                <td>........................................</td>
                <td>........................................</td>
            </tr>
        </table>


    </div>
    <script>
    document.getElementById('print-btn').addEventListener('click', function() {
        var container = document.getElementById('print-container');
        container.style.display = 'none';
        window.print();
        container.style.display = '';
    });
    </script>
</body>

</html>