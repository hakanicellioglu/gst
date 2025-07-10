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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $width = (float)($_POST['width'] ?? 0);
    $height = (float)($_POST['height'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

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
    $motor_kutu_contasi = (($motor_kutusu * $quantity) + ($alt_kasa * $quantity)) / 1000;
    $kanat_contasi = $kanat * $quantity * 2 / 1000;
    $kenet_fitili = (($tutamak * $quantity) + ($kenetli_baza * $quantity)) / 1000;
    $kil_fitil = (($dikme * $quantity) + ($orta_dikme * $quantity * 2) + ($son_kapatma * $quantity) + ($kanat * $quantity)) / 1000;

    $results = [
        'Motor Kutusu' => $motor_kutusu,
        'Motor Kapak' => $motor_kapak,
        'Alt Kasa' => $alt_kasa,
        'Tutamak' => $tutamak,
        'Kenetli Baza' => $kenetli_baza,
        'Küpeşte Baza' => $kupeste_baza,
        'Küpeşte' => $kupeste,
        'Yatak Tek Cam Çıtası' => $yatak_citasi,
        'Dikey Tek Cam Çıtası' => $dikey_citasi,
        'Dikme' => $dikme,
        'Orta Dikme' => $orta_dikme,
        'Son Kapatma' => $son_kapatma,
        'Kanat' => $kanat,
        'Dikey Baza' => $dikey_baza,
        'Zincir' => $zincir,
        'Flatbelt Kayış' => $flatbelt_kayis,
        'Motor Borusu' => $motor_borusu,
        'Motor Kutu Contası (m)' => $motor_kutu_contasi,
        'Kanat Contası (m)' => $kanat_contasi,
        'Kenet Fitili (m)' => $kenet_fitili,
        'Kıl Fitil (m)' => $kil_fitil,
    ];
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
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Hesapla</button>
        </form>
        <?php if ($results): ?>
            <table class="table table-bordered">
                <tbody>
                <?php foreach ($results as $key => $value): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo round($value, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
