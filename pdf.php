<?php
require_once 'config.php';
// PDF artik tarayıcıda olusturulacagi icin tFPDF kaldirildi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$simple   = isset($_GET['simple']);
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

    $cam_en = round($width - 219);
    $cam_boy = round($dikey_baza + 28 - 2);

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

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .proposal-document {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
            padding: 20px;
        }
        .proposal-document h2 {
            background-color: #e9ecef;
            padding: 6px;
            border-radius: .25rem;
        }
        .proposal-document table thead {
            background-color: #343a40;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container my-3">
    <?php if (!$simple): ?>
    <div class="mb-3">
        <button class="btn btn-primary" onclick="generatePDF()">PDF İndir</button>
        <button class="btn btn-secondary" onclick="printProposal()">Yazdır</button>
    </div>
    <?php endif; ?>
    <div class="proposal-document">
        <h1 class="h4 text-center mb-3">Teklif Bilgileri</h1>
        <p><strong>Firma:</strong> <?php echo htmlspecialchars($quote['company_name']); ?></p>
        <p><strong>Müşteri:</strong> <?php echo htmlspecialchars($quote['customer_name']); ?></p>
        <p><strong>Tarih:</strong> <?php echo htmlspecialchars($quote['quote_date']); ?></p>
        <?php if ($simple): ?>
            <?php foreach ($guillotines as $g): ?>
                <?php $opt = compute_optimization($pdo, (float)$g['width_mm'], (float)$g['height_mm'], (int)$g['system_qty'], (string)$g['glass_type']); ?>
                <p><?php echo $g['width_mm']; ?> x <?php echo $g['height_mm']; ?> mm - <?php echo round($opt['sales']); ?></p>
            <?php endforeach; ?>
        <?php else: ?>
        <?php foreach ($guillotines as $g): ?>
            <h2 class="h5 mt-4">Giyotin Sistem (<?php echo $g['width_mm']; ?> x <?php echo $g['height_mm']; ?> mm)</h2>
            <p>Adet: <?php echo $g['system_qty']; ?> | Cam: <?php echo htmlspecialchars($g['glass_type']); ?></p>
            <?php $opt = compute_optimization($pdo, (float)$g['width_mm'], (float)$g['height_mm'], (int)$g['system_qty'], (string)$g['glass_type']); ?>
            <?php foreach ($opt['grouped'] as $cat => $rows): ?>
                <h3 class="h6 mt-3"><?php echo $cat; ?></h3>
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Parça</th>
                            <th>Uzunluk</th>
                            <th>Adet</th>
                            <th>Maliyet</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $len = is_numeric($row['length']) ? round($row['length']) : $row['length']; ?>
                        <?php $cost = is_null($row['cost']) ? '-' : round($row['cost']); ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $len; ?></td>
                            <td><?php echo $row['count']; ?></td>
                            <td><?php echo $cost; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <p><strong>Toplam Maliyet:</strong> <?php echo round($opt['total']); ?></p>
            <p><strong>Toplam Fiyat:</strong> <?php echo round($opt['sales']); ?></p>
        <?php endforeach; ?>

        <?php if ($slidings): ?>
            <h2 class="h5 mt-4">Sürme Sistemler</h2>
            <table class="table table-sm table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Sistem Tipi</th>
                        <th>En</th>
                        <th>Boy</th>
                        <th>Adet</th>
                        <th>Renk</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($slidings as $s): ?>
                    <tr>
                        <td><?php echo $s['system_type']; ?></td>
                        <td><?php echo $s['width_mm']; ?></td>
                        <td><?php echo $s['height_mm']; ?></td>
                        <td><?php echo $s['system_qty']; ?></td>
                        <td><?php echo $s['ral_code']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script>
function generatePDF() {
    if (typeof html2pdf === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = function() { createPDF(); };
        document.head.appendChild(script);
    } else {
        createPDF();
    }
}
function createPDF() {
    const element = document.querySelector('.proposal-document');
    const proposalTitle = 'Teklif';
    const proposalNumber = 'TKF-<?php echo $quote_id; ?>';
    const opt = {
        margin: [5,5,5,5],
        filename: `${proposalNumber} - ${proposalTitle}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, letterRendering: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
function printProposal() {
    const originalTitle = document.title;
    const proposalTitle = 'Teklif';
    const proposalNumber = 'TKF-<?php echo $quote_id; ?>';
    document.title = `${proposalNumber} - ${proposalTitle}`;
    window.print();
    setTimeout(() => { document.title = originalTitle; }, 1000);
}
function updateStatus(newStatus) {
    const statusLabels = {
        'sent': 'gönderildi olarak işaretlemek',
        'accepted': 'kabul edildi olarak işaretlemek',
        'rejected': 'reddedildi olarak işaretlemek'
    };
    if (confirm(`Bu teklifi ${statusLabels[newStatus]} istediğinizden emin misiniz?`)) {
        const buttons = document.querySelectorAll('button[onclick*="updateStatus"]');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Güncelleniyor...';
        });
        fetch('proposal-update-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: <?php echo $quote_id; ?>, status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof notifications !== 'undefined') {
                    notifications.success(data.message);
                } else {
                    alert(data.message);
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                if (typeof notifications !== 'undefined') {
                    notifications.error(data.message || 'Durum güncellenirken hata oluştu');
                } else {
                    alert(data.message || 'Durum güncellenirken hata oluştu');
                }
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = btn.getAttribute('data-original-text') || btn.innerHTML;
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Durum güncellenirken hata oluştu');
            } else {
                alert('Durum güncellenirken hata oluştu');
            }
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = btn.getAttribute('data-original-text') || btn.innerHTML;
            });
        });
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button[onclick*="updateStatus"]');
    buttons.forEach(btn => {
        btn.setAttribute('data-original-text', btn.innerHTML);
    });
});
</script>
<?php if ($simple): ?>
<script>
document.addEventListener('DOMContentLoaded', generatePDF);
</script>
<?php endif; ?>
</body>
</html>
