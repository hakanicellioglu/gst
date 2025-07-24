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
load_theme_settings($pdo);

$results = [];
$width = '';
$height = '';
$quantity = 1;
$glass_type = 'Isıcam';
$profit_margin = 0;
$returnPrice = false;
$offerId = 0;
$offerExists = true;

$input = array_merge($_GET, $_POST);
$hasInput = $_SERVER['REQUEST_METHOD'] === 'POST' ||
    (!empty($input['width']) && !empty($input['height']));

if ($hasInput) {
    $offerId = (int) ($input['id'] ?? 0);
    if ($offerId) {
        $stmt = $pdo->prepare('SELECT id FROM master_quotes WHERE id = ?');
        $stmt->execute([$offerId]);
        $offerExists = $stmt->fetchColumn() !== false;
    }
    $width = (float) ($input['width'] ?? 0);
    $height = (float) ($input['height'] ?? 0);
    $quantity = max(1, (int) ($input['quantity'] ?? 1));
    $glass_type = $input['glass_type'] ?? $glass_type;
    $profit_margin = (float) ($input['profit_margin'] ?? 0);
    $returnPrice = isset($input['return']);

    if (!empty($input['gid'])) {
        $stmt = $pdo->prepare('SELECT glass_type FROM guillotine_quotes WHERE id = ?');
        $stmt->execute([$input['gid']]);
        $dbGlass = $stmt->fetchColumn();
        if ($dbGlass !== false) {
            $glass_type = $dbGlass;
        }
    }

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

    // Cam ölçüleri
    $cam_en = round($width - 219);
    $cam_boy = round($dikey_baza + 28 - 2);

    // parça adet hesapları
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
    $motor_kutu_contasi = (($motor_kutusu * $motor_kutusu_qty) + ($alt_kasa * $alt_kasa_qty)) / 1000;
    $kanat_contasi = ($kanat * $kanat_qty) / 1000;
    $kenet_fitili = (($tutamak * $quantity) + ($kenetli_baza * $quantity)) / 1000;
    $kil_fitil = (
        ($tutamak * $tutamak_qty) +
        ($kenetli_baza * $kenetli_baza_qty) +
        ($dikme * $dikme_qty) +
        ($orta_dikme * $orta_dikme_qty * 2) +
        ($son_kapatma * $son_kapatma_qty) +
        ($kanat * $kanat_qty)
    ) / 1000;

    $cam_adet = ($kanat_qty + $dikey_baza_qty) / 2;

    $results = [
        ['name' => 'Motor Kutusu', 'length' => $motor_kutusu, 'count' => $motor_kutusu_qty],
        ['name' => 'Motor Kapak', 'length' => $motor_kapak, 'count' => $motor_kapak_qty],
        ['name' => 'Alt Kasa', 'length' => $alt_kasa, 'count' => $alt_kasa_qty],
        ['name' => 'Tutamak', 'length' => $tutamak, 'count' => $tutamak_qty],
        ['name' => 'Kenetli Baza', 'length' => $kenetli_baza, 'count' => $kenetli_baza_qty],
        ['name' => 'Küpeşte Bazası', 'length' => $kupeste_baza, 'count' => $kupeste_baza_qty],
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
        ['name' => 'Motor Kutu Contası (m)', 'length' => '-', 'count' => $motor_kutu_contasi],
        ['name' => 'Kanat Contası (m)', 'length' => '-', 'count' => $kanat_contasi],
        ['name' => 'Kenet Fitili (m)', 'length' => '-', 'count' => $kenet_fitili],
        ['name' => 'Kıl Fitil (m)', 'length' => '-', 'count' => $kil_fitil],
    ];


    $nameAliasMap = [
        "Yatay Tek Cam Çıtası" => "Tek Cam Çıtası",
        "Dikey Tek Cam Çıtası" => "Tek Cam Çıtası",
        "Motor Kutu Contası (m)" => "Motor Kutu Contası",
        "Kanat Contası (m)" => "Kanat Contası",
        "Kenet Fitili (m)" => "Kenet Fitili",
        "Kıl Fitil (m)" => "Kıl Fitil",
    ];
    $total_cost = 0;
    foreach ($results as &$row) {
        $lookupName = $nameAliasMap[$row['name']] ?? $row['name'];
        $stmt = $pdo->prepare('SELECT unit, measure_value, unit_price, category, image_data, image_type FROM products WHERE name = ? LIMIT 1');
        $stmt->execute([$lookupName]);
        $product = $stmt->fetch();
        $row['cost'] = null;
        $row['category'] = $product['category'] ?? 'Diğer';
        if (!empty($product['image_data'])) {
            $row['image_src'] = 'data:' . $product['image_type'] . ';base64,' . base64_encode($product['image_data']);
        } else {
            $row['image_src'] = null;
        }
        if ($product) {
            $count = is_numeric($row['count']) ? (float) $row['count'] : 0;
            $length = is_numeric($row['length']) ? (float) $row['length'] : 0;
            if (strpos($row['name'], '(m)') === false && is_numeric($row['length'])) {
                $length = $row['length'] / 1000; // convert mm to m
            }
            if (strtolower($product['category']) === 'fitil') {
                // Fitil kategorisinde maliyet hesaplaması metre x birim fiyat şeklinde
                $row['cost'] = $length * $count * $product['unit_price'];
            } else {
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
            }
            $total_cost += $row['cost'];
        }
    }
    unset($row);
    $groupedResults = [];
    foreach ($results as $r) {
        $groupedResults[$r['category']][] = $r;
    }
    $categoryOrder = ['Cam', 'Alüminyum', 'Aksesuar', 'Fitil', 'Diğer'];
    $sales_price = $total_cost * (1 + $profit_margin / 100);

    if ($offerId && $offerExists && !empty($input['gid'])) {
        $stmt = $pdo->prepare('UPDATE guillotine_quotes SET total_price=? WHERE id=?');
        $stmt->execute([round($sales_price, 2), (int)$input['gid']]);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Optimizasyon</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <h2 class="mb-4">Optimizasyon Hesaplama</h2>
        <?php if (!$offerExists && $offerId): ?>
        <div class="alert alert-warning">Teklif bulunmadı.</div>
        <?php endif; ?>
        <form method="post" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Genişliği</label>
                <input type="number" step="0.01" name="width" class="form-control" required
                    value="<?php echo htmlspecialchars($width); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Yüksekliği</label>
                <input type="number" step="0.01" name="height" class="form-control" required
                    value="<?php echo htmlspecialchars($height); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Adet</label>
                <input type="number" name="quantity" class="form-control" min="1"
                    value="<?php echo htmlspecialchars($quantity); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Cam Tipi</label>
                <select name="glass_type" class="form-select">
                    <option value="Isıcam" <?php echo $glass_type === 'Isıcam' ? 'selected' : ''; ?>>Isıcam</option>
                    <option value="Tek Cam" <?php echo $glass_type === 'Tek Cam' ? 'selected' : ''; ?>>Tek Cam</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Kar Marjı (%)</label>
                <input type="number" step="0.01" name="profit_margin" class="form-control"
                    value="<?php echo htmlspecialchars($profit_margin); ?>">
            </div>
            <input type="hidden" name="gid" value="<?php echo htmlspecialchars($input['gid'] ?? ''); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($offerId); ?>">
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Hesapla</button>
        </form>
        <?php if ($results): ?>
        <table class="table table-bordered mb-4">
            <thead>
                <tr class="table-secondary">
                    <th colspan="4">Cam</th>
                </tr>
                <tr>
                    <th>En</th>
                    <th>Boy</th>
                    <th>Adet</th>
                    <th>m²</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedResults['Cam'] ?? [] as $row): ?>
                <?php
                        $dims = preg_split('/[xX]/', $row['length']);
                        $en = isset($dims[0]) && is_numeric(trim($dims[0])) ? round(trim($dims[0])) : trim($dims[0] ?? '');
                        $boy = isset($dims[1]) && is_numeric(trim($dims[1])) ? round(trim($dims[1])) : trim($dims[1] ?? '');
                        $adet = is_numeric($row['count']) ? (int) $row['count'] : $row['count'];
                        $m2 = (is_numeric($en) && is_numeric($boy) && is_numeric($row['count']))
                            ? number_format($en * $boy * $row['count'] / 1e6, 2)
                            : '-';
                        ?>
                <tr>
                    <td><?php echo htmlspecialchars($en); ?></td>
                    <td><?php echo htmlspecialchars($boy); ?></td>
                    <td><?php echo htmlspecialchars($adet); ?></td>
                    <td><?php echo $m2; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                <tr class="table-secondary">
                    <th colspan="4">Alüminyum</th>
                </tr>
                <th>Parça</th>
                <th>Uzunluk</th>
                <th>Adet</th>
                <th>Maliyet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedResults['Alüminyum'] ?? [] as $row): ?>
                <tr>
                    <th>
                        <?php if (!empty($row['image_src'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_src']); ?>" alt="" class="me-2 opt-img">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </th>
                    <td>
                        <?php
                                echo is_numeric($row['length'])
                                    ? round($row['length'])
                                    : htmlspecialchars($row['length']);
                                ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['count']); ?></td>
                    <td>
                        <?php
                                echo is_null($row['cost']) 
                                    ? '-'
                                    : number_format(round($row['cost']), 0);
                                ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered mb-4">
            <thead>
                <tr class="table-secondary">
                    <th colspan="4">Aksesuar ve Fitil</th>
                </tr>
                <tr>
                    <th>Parça</th>
                    <th>Uzunluk</th>
                    <th>Adet</th>
                    <th>Maliyet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (['Aksesuar', 'Fitil', 'Diğer'] as $cat): ?>
                <?php if (!empty($groupedResults[$cat])): ?>
                <?php foreach ($groupedResults[$cat] as $row): ?>
                <tr>
                    <th>
                        <?php if (!empty($row['image_src'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_src']); ?>" alt="" class="me-2 opt-img">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </th>
                    <td>
                        <?php
                                        echo is_numeric($row['length'])
                                            ? round($row['length'])
                                            : htmlspecialchars($row['length']);
                                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['count']); ?></td>
                    <td>
                        <?php
                                        echo is_null($row['cost'])
                                            ? '-'
                                            : number_format(round($row['cost']), 0);
                                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" class="text-end">Toplam Maliyet</th>
                    <th><?php echo number_format(round($total_cost), 0); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Toplam Fiyat (Kar Dahil)</th>
                    <th><?php echo number_format(round($sales_price), 0); ?></th>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php if ($returnPrice && $results): ?>
    <script>
    if (window.opener) {
        window.opener.postMessage({
            price: <?php echo json_encode(round($sales_price, 2)); ?>
        }, '*');
        window.close();
    }
    </script>
    <?php endif; ?>
</body>

</html>