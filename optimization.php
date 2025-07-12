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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $width = (float)($_POST['width'] ?? 0);
    $height = (float)($_POST['height'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $glass_type = $_POST['glass_type'] ?? $glass_type;

    if (!empty($_POST['gid'])) {
        $stmt = $pdo->prepare('SELECT glass_type FROM guillotine_quotes WHERE id = ?');
        $stmt->execute([$_POST['gid']]);
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
    $cam_en = $width - 221;
    $cam_boy = $dikey_baza + 26;

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

    $total_cost = 0;
    foreach ($results as &$row) {
        $stmt = $pdo->prepare('SELECT unit, measure_value, unit_price FROM products WHERE name = ? LIMIT 1');
        $stmt->execute([$row['name']]);
        $product = $stmt->fetch();
        $row['cost'] = null;
        if ($product) {
            $count = is_numeric($row['count']) ? (float)$row['count'] : 0;
            $length = is_numeric($row['length']) ? (float)$row['length'] : 0;
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
        <form method="post" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Genişliği</label>
                <input type="number" step="0.01" name="width" class="form-control" required value="<?php echo htmlspecialchars($width); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Yüksekliği</label>
                <input type="number" step="0.01" name="height" class="form-control" required value="<?php echo htmlspecialchars($height); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Adet</label>
                <input type="number" name="quantity" class="form-control" min="1" value="<?php echo htmlspecialchars($quantity); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Cam Tipi</label>
                <select name="glass_type" class="form-select">
                    <option value="Isıcam" <?php echo $glass_type === 'Isıcam' ? 'selected' : ''; ?>>Isıcam</option>
                    <option value="Tek Cam" <?php echo $glass_type === 'Tek Cam' ? 'selected' : ''; ?>>Tek Cam</option>
                </select>
            </div>
            <input type="hidden" name="gid" value="<?php echo htmlspecialchars($_POST['gid'] ?? ''); ?>">
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Hesapla</button>
        </form>
        <?php if ($results): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Parça</th>
                        <th>Uzunluk</th>
                        <th>Adet</th>
                        <th>Maliyet</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($row['name']); ?></th>
                        <td>
                            <?php
                                echo is_numeric($row['length'])
                                    ? round($row['length'], 2)
                                    : htmlspecialchars($row['length']);
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['count']); ?></td>
                        <td>
                            <?php echo is_null($row['cost']) ? '-' : number_format($row['cost'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" class="text-end">Toplam Maliyet</th>
                    <th><?php echo number_format($total_cost, 2); ?></th>
                </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
