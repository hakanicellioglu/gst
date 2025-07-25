<?php
declare(strict_types=1);

require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/settings.php';
require_once 'helpers/validation.php';
require_once 'helpers/utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

load_theme_settings($pdo);

/**
 * Main optimization calculation.
 *
 * @param array $input
 * @param PDO   $pdo
 * @return array
 */
function calculateOptimization(array $input, PDO $pdo): array
{
    $errors = [];

    $width  = validate_float($input['width'] ?? null);
    $height = validate_float($input['height'] ?? null);
    $quantity = validate_int($input['quantity'] ?? 1) ?? 1;
    $glassType = sanitize_string($input['glass_type'] ?? 'Isıcam');
    $profitMargin = validate_float($input['profit_margin'] ?? 0.0) ?? 0.0;

    if ($width === null || $width <= 0) {
        $errors[] = 'Width must be greater than zero.';
    }
    if ($height === null || $height <= 0) {
        $errors[] = 'Height must be greater than zero.';
    }
    if ($quantity <= 0) {
        $errors[] = 'Quantity must be positive.';
        $quantity = 1;
    }
    if ($profitMargin < 0) {
        $errors[] = 'Profit margin cannot be negative.';
        $profitMargin = 0.0;
    }

    if ($errors) {
        return ['errors' => $errors];
    }

    $motorKutusu = $width - 14;
    $motorKapak = $motorKutusu - 1;
    $altKasa = $width;
    $tutamak = $width - 185;
    $kenetliBaza = $width - 185;
    $kupesteBaza = $width - 185;
    $kupeste = $width - 185;
    $dikeyBaza = ($height - 290) / 3;
    $kanat = $dikeyBaza;
    $dikme = $height - 166;
    $ortaDikme = $dikme;
    $sonKapatma = $height - $kanat - 221;
    $yatakCitasi = $kenetliBaza - 52;
    $dikeyCitasi = $dikeyBaza - 5;
    $zincir = $sonKapatma + 600;
    $flatbeltKayis = $sonKapatma + 600;
    $motorBorusu = $width - 59;

    $camEn = round($width - 219);
    $camBoy = round($dikeyBaza + 28 - 2);

    $motorKutusuQty = $quantity;
    $motorKapakQty = $quantity;
    $altKasaQty = $quantity;
    $kenetliBazaQty = 2 * $quantity;
    $kupesteBazaQty = 2 * $quantity;
    $tutamakQty = 6 * $quantity - $kenetliBazaQty - $kupesteBazaQty;
    $kupesteQty = $quantity;
    if (strtolower($glassType) === 'tek cam') {
        $yatayCitasiQty = 6 * $quantity;
        $dikeyCitasiQty = 6 * $quantity;
    } else {
        $yatayCitasiQty = 0;
        $dikeyCitasiQty = 0;
    }
    $dikmeQty = 2 * $quantity;
    $ortaDikmeQty = 2 * $quantity;
    $sonKapatmaQty = 2 * $quantity;
    $kanatQty = 2 * $quantity;
    $dikeyBazaQty = 4 * $quantity;
    $zincirQty = 2 * $quantity;
    $motorBorusuQty = $quantity;
    $motorKutuContasi = (($motorKutusu * $motorKutusuQty) + ($altKasa * $altKasaQty)) / 1000;
    $kanatContasi = ($kanat * $kanatQty) / 1000;
    $kenetFitili = (($tutamak * $quantity) + ($kenetliBaza * $quantity)) / 1000;
    $kilFitil = (
        ($tutamak * $tutamakQty) +
        ($kenetliBaza * $kenetliBazaQty) +
        ($dikme * $dikmeQty) +
        ($ortaDikme * $ortaDikmeQty * 2) +
        ($sonKapatma * $sonKapatmaQty) +
        ($kanat * $kanatQty)
    ) / 1000;

    $camAdet = ($kanatQty + $dikeyBazaQty) / 2;

    $results = [
        ['name' => 'Motor Kutusu', 'length' => $motorKutusu, 'count' => $motorKutusuQty],
        ['name' => 'Motor Kapak', 'length' => $motorKapak, 'count' => $motorKapakQty],
        ['name' => 'Alt Kasa', 'length' => $altKasa, 'count' => $altKasaQty],
        ['name' => 'Tutamak', 'length' => $tutamak, 'count' => $tutamakQty],
        ['name' => 'Kenetli Baza', 'length' => $kenetliBaza, 'count' => $kenetliBazaQty],
        ['name' => 'Küpeşte Bazası', 'length' => $kupesteBaza, 'count' => $kupesteBazaQty],
        ['name' => 'Küpeşte', 'length' => $kupeste, 'count' => $kupesteQty],
        ['name' => 'Yatay Tek Cam Çıtası', 'length' => $yatakCitasi, 'count' => $yatayCitasiQty],
        ['name' => 'Dikey Tek Cam Çıtası', 'length' => $dikeyCitasi, 'count' => $dikeyCitasiQty],
        ['name' => 'Dikme', 'length' => $dikme, 'count' => $dikmeQty],
        ['name' => 'Orta Dikme', 'length' => $ortaDikme, 'count' => $ortaDikmeQty],
        ['name' => 'Son Kapatma', 'length' => $sonKapatma, 'count' => $sonKapatmaQty],
        ['name' => 'Kanat', 'length' => $kanat, 'count' => $kanatQty],
        ['name' => 'Dikey Baza', 'length' => $dikeyBaza, 'count' => $dikeyBazaQty],
        ['name' => 'Cam', 'length' => $camEn . ' x ' . $camBoy, 'count' => $camAdet],
        ['name' => 'Zincir', 'length' => $zincir, 'count' => $zincirQty],
        ['name' => 'Flatbelt Kayış', 'length' => $flatbeltKayis, 'count' => $quantity],
        ['name' => 'Motor Borusu', 'length' => $motorBorusu, 'count' => $motorBorusuQty],
        ['name' => 'Motor Kutu Contası (m)', 'length' => $motorKutuContasi, 'count' => 1],
        ['name' => 'Kanat Contası (m)', 'length' => $kanatContasi, 'count' => 1],
        ['name' => 'Kenet Fitili (m)', 'length' => $kenetFitili, 'count' => 1],
        ['name' => 'Kıl Fitil (m)', 'length' => $kilFitil, 'count' => 1],
    ];

    $nameAliasMap = [
        'Yatay Tek Cam Çıtası' => 'Tek Cam Çıtası',
        'Dikey Tek Cam Çıtası' => 'Tek Cam Çıtası',
        'Motor Kutu Contası (m)' => 'Motor Kutu Contası',
        'Kanat Contası (m)' => 'Kanat Contası',
        'Kenet Fitili (m)' => 'Kenet Fitili',
        'Kıl Fitil (m)' => 'Kıl Fitil',
    ];

    $lookup = [];
    foreach ($results as $row) {
        $lookupName = $nameAliasMap[$row['name']] ?? $row['name'];
        $lookup[$lookupName] = true;
    }

    $products = [];
    if ($lookup) {
        $placeholders = rtrim(str_repeat('?,', count($lookup)), ',');
        try {
            $stmt = $pdo->prepare(
                "SELECT name, unit, measure_value, unit_price, category, image_data, image_type" .
                " FROM products WHERE name IN ($placeholders)"
            );
            $stmt->execute(array_keys($lookup));
            foreach ($stmt->fetchAll() as $p) {
                $products[$p['name']] = $p;
            }
        } catch (PDOException $e) {
            $errors[] = 'Product lookup failed: ' . $e->getMessage();
        }
    }

    $totalCost = 0.0;
    $totalWeight = 0.0;

    $aluminumPrice = (float) get_setting($pdo, 'aluminum_cost_per_kg');

    foreach ($results as &$row) {
        $lookupName = $nameAliasMap[$row['name']] ?? $row['name'];
        $product = $products[$lookupName] ?? null;
        $row['cost'] = null;
        $row['weight'] = null;
        $row['category'] = $product['category'] ?? 'Diğer';
        $row['image_src'] = null;
        if ($product && !empty($product['image_data'])) {
            $row['image_src'] = 'data:' . $product['image_type'] . ';base64,' . base64_encode($product['image_data']);
        }
        if ($product) {
            $count = (float) $row['count'];
            $length = is_numeric($row['length']) ? (float) $row['length'] : 0.0;
            if (strpos($row['name'], '(m)') === false && is_numeric($row['length'])) {
                $length = $row['length'] / 1000;
            }
            if (strtolower($product['category']) === 'fitil') {
                $row['cost'] = $length * $count * $product['unit_price'];
            } else {
                switch (strtolower($product['unit'])) {
                    case 'adet':
                        $row['cost'] = $count * $product['unit_price'];
                        break;
                    case 'kg':
                        $weightPerPiece = $length * $product['measure_value'];
                        $row['weight'] = $weightPerPiece * $count;
                        if (strtolower($product['category']) === 'alüminyum') {
                            $product['unit_price'] = $aluminumPrice;
                            $row['weight'] *= 1.01;
                            $totalWeight += $row['weight'];
                        }
                        $row['cost'] = $row['weight'] * $product['unit_price'];
                        break;
                    default:
                        $row['cost'] = $length * $count * $product['unit_price'];
                        break;
                }
            }
            $row['cost'] = max(0, $row['cost']);
            $totalCost += $row['cost'];
        } else {
            $errors[] = $row['name'] . ' için ürün bulunamadı.';
            $row['cost'] = 0.0;
        }
    }
    unset($row);

    $glassArea = 0.0;
    foreach ($results as $r) {
        if (strtolower($r['name']) === 'cam') {
            $dims = preg_split('/[xX]/', (string) $r['length']);
            if (count($dims) >= 2 && is_numeric(trim($dims[0])) && is_numeric(trim($dims[1]))) {
                $w = (float) trim($dims[0]) / 1000;
                $h = (float) trim($dims[1]) / 1000;
                $glassArea = $w * $h * (float) $r['count'];
            }
            break;
        }
    }

    $glassPrice = (float) get_setting($pdo, 'glass_cost_per_sqm');
    if ($glassArea > 0) {
        $glassCost = $glassArea * $glassPrice;
        $results[] = [
            'name' => 'Glass Cost (m²)',
            'length' => $glassArea,
            'count' => null,
            'cost' => $glassCost,
            'category' => 'Cam',
            'image_src' => null,
        ];
        $totalCost += $glassCost;
    }

    $groupedResults = [];
    foreach ($results as $r) {
        $groupedResults[$r['category']][] = $r;
    }

    $aluminumWaste = $totalWeight * 0.07;
    $aluminumWasteCost = $aluminumWaste * $aluminumPrice;
    $aluminumTotalCost = ($totalWeight + $aluminumWaste) * $aluminumPrice;
    $totalCost += $aluminumWasteCost;

    if ($totalCost < 0) {
        $totalCost = 0.0;
    }

    $marginFactor = 1 - ($profitMargin / 100);
    if ($marginFactor > 0) {
        $salesPrice = max(0, $totalCost / $marginFactor);
    } else {
        $salesPrice = 0.0;
    }
    $calculatedMargin = $salesPrice > 0 ? (($salesPrice - $totalCost) / $salesPrice) * 100 : 0.0;
    $profitMarginAmount = $salesPrice - $totalCost;

    $interestRate = (float) get_setting($pdo, 'monthly_interest_rate');
    $priceWithInterest = max(0, $salesPrice * (1 + $interestRate));

    return [
        'errors' => $errors,
        'groupedResults' => $groupedResults,
        'total_cost' => $totalCost,
        'total_weight' => $totalWeight,
        'aluminum_waste' => $aluminumWaste,
        'aluminum_waste_cost' => $aluminumWasteCost,
        'aluminum_total_cost' => $aluminumTotalCost,
        'sales_price' => $salesPrice,
        'calculated_margin' => $calculatedMargin,
        'profit_margin_amount' => $profitMarginAmount,
        'price_with_interest' => $priceWithInterest,
        'inputs' => [
            'width' => $width,
            'height' => $height,
            'quantity' => $quantity,
            'glass_type' => $glassType,
            'profit_margin' => $profitMargin,
        ],
    ];
}

$input = array_merge($_GET, $_POST);
$data = [];
$returnPrice = false;
$offerId = validate_int($input['id'] ?? null) ?? 0;
$offerExists = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (!empty($input['width']) && !empty($input['height']))) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $data['errors'][] = 'Invalid CSRF token.';
    } else {
        if ($offerId) {
            try {
                $stmt = $pdo->prepare('SELECT id FROM master_quotes WHERE id = ?');
                $stmt->execute([$offerId]);
                $offerExists = $stmt->fetchColumn() !== false;
            } catch (PDOException $e) {
                $offerExists = false;
            }
        }
        $returnPrice = isset($input['return']);
        $data = calculateOptimization($input, $pdo);
        if ($offerId && $offerExists && !empty($input['gid']) && empty($data['errors'])) {
            try {
                $stmt = $pdo->prepare('UPDATE guillotine_quotes SET total_price=?, profit_margin_rate=?, profit_margin_amount=? WHERE id=?');
                $stmt->execute([
                    round($data['sales_price'], 2),
                    round($data['calculated_margin'], 2),
                    round($data['profit_margin_amount'], 2),
                    (int)$input['gid']
                ]);
            } catch (PDOException $e) {
                $data['errors'][] = 'Offer update failed: ' . $e->getMessage();
            }
        }
    }
}

$inputs = $data['inputs'] ?? [
    'width' => '',
    'height' => '',
    'quantity' => 1,
    'glass_type' => 'Isıcam',
    'profit_margin' => 0,
];
$errors = $data['errors'] ?? [];
$groupedResults = $data['groupedResults'] ?? [];
$total_cost = $data['total_cost'] ?? 0.0;
$total_weight = $data['total_weight'] ?? 0.0;
$aluminum_waste = $data['aluminum_waste'] ?? 0.0;
$aluminum_waste_cost = $data['aluminum_waste_cost'] ?? 0.0;
$aluminum_total_cost = $data['aluminum_total_cost'] ?? 0.0;
$sales_price = $data['sales_price'] ?? 0.0;
$price_with_interest = $data['price_with_interest'] ?? 0.0;
$calculated_margin = $data['calculated_margin'] ?? 0.0;
$profit_margin = $inputs['profit_margin'];
$width = $inputs['width'];
$height = $inputs['height'];
$quantity = $inputs['quantity'];
$glass_type = $inputs['glass_type'];

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
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
        <?php endif; ?>
        <?php if (!$offerExists && $offerId): ?>
        <div class="alert alert-warning">Teklif bulunmadı.</div>
        <?php endif; ?>
        <form method="post" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Genişliği</label>
                <input type="number" step="0.01" name="width" class="form-control" required value="<?php echo htmlspecialchars((string)$width); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Giyotin Sistemi Yüksekliği</label>
                <input type="number" step="0.01" name="height" class="form-control" required value="<?php echo htmlspecialchars((string)$height); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Adet</label>
                <input type="number" name="quantity" class="form-control" min="1" value="<?php echo htmlspecialchars((string)$quantity); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Cam Tipi</label>
                <select name="glass_type" class="form-select" disabled>
                    <option value="Isıcam" <?php echo $glass_type === 'Isıcam' ? 'selected' : ''; ?>>Isıcam</option>
                    <option value="Tek Cam" <?php echo $glass_type === 'Tek Cam' ? 'selected' : ''; ?>>Tek Cam</option>
                </select>
                <input type="hidden" name="glass_type" value="<?php echo htmlspecialchars($glass_type); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Kar Marjı (%)</label>
                <input type="number" step="5.0" name="profit_margin" class="form-control" value="<?php echo htmlspecialchars((string)$profit_margin); ?>">
            </div>
            <input type="hidden" name="gid" value="<?php echo htmlspecialchars($input['gid'] ?? ''); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$offerId); ?>">
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Hesapla</button>
        </form>
        <?php if ($groupedResults): ?>
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
                    $dims = preg_split('/[xX]/', (string) $row['length']);
                    $en = isset($dims[0]) && is_numeric(trim($dims[0])) ? round((float) trim($dims[0])) : trim($dims[0] ?? '');
                    $boy = isset($dims[1]) && is_numeric(trim($dims[1])) ? round((float) trim($dims[1])) : trim($dims[1] ?? '');
                    $adet = is_numeric($row['count']) ? (int) $row['count'] : $row['count'];
                    $m2 = (is_numeric($en) && is_numeric($boy) && is_numeric($row['count']))
                        ? fmt_currency($en * $boy * $row['count'] / 1e6, 2)
                        : '-';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$en); ?></td>
                    <td><?php echo htmlspecialchars((string)$boy); ?></td>
                    <td><?php echo htmlspecialchars((string)$adet); ?></td>
                    <td><?php echo $m2; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered mb-4">
            <thead>
                <tr class="table-secondary">
                    <th colspan="3">Cam Maliyeti</th>
                </tr>
                <tr>
                    <th>Parça</th>
                    <th>m²</th>
                    <th>Maliyet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedResults['Cam'] ?? [] as $row): ?>
                <?php if ($row['name'] === 'Glass Cost (m²)'): ?>
                <tr>
                    <th><?php echo htmlspecialchars($row['name']); ?></th>
                    <td><?php echo fmt_currency($row['length'], 2); ?></td>
                    <td><?php echo fmt_currency($row['cost']); ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table table-bordered mb-4">
            <thead>
                <tr class="table-secondary">
                    <th colspan="5">Alüminyum</th>
                </tr>
                <tr>
                    <th>Parça</th>
                    <th>Uzunluk</th>
                    <th>Adet</th>
                    <th>Ağırlık (kg)</th>
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
                    <td><?php echo is_numeric($row['length']) ? round((float) $row['length']) : htmlspecialchars((string)$row['length']); ?></td>
                    <td><?php echo htmlspecialchars((string)$row['count']); ?></td>
                    <td><?php echo is_null($row['weight']) ? '-' : fmt_currency($row['weight'], 2); ?></td>
                    <td><?php echo is_null($row['cost']) ? '-' : fmt_currency($row['cost']); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" class="text-end">Toplam Alüminyum Ağırlığı</th>
                    <th><?php echo fmt_currency($total_weight, 2); ?></th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Alüminyum Fire (7%)</th>
                    <th><?php echo fmt_currency($aluminum_waste, 2); ?></th>
                    <th><?php echo fmt_currency($aluminum_waste_cost); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Toplam Alüminyum Maliyeti</th>
                    <th><?php echo fmt_currency($total_weight + $aluminum_waste, 2); ?></th>
                    <th><?php echo fmt_currency($aluminum_total_cost); ?></th>
                </tr>
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
                    <td><?php echo is_numeric($row['length']) ? round((float) $row['length']) : htmlspecialchars((string)$row['length']); ?></td>
                    <td><?php echo htmlspecialchars((string)$row['count']); ?></td>
                    <td><?php echo is_null($row['cost']) ? '-' : fmt_currency($row['cost']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" class="text-end">Toplam Maliyet</th>
                    <th><?php echo fmt_currency($total_cost); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Toplam Fiyat (Kar Dahil)</th>
                    <th><?php echo fmt_currency($sales_price); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Taksitli Fiyat</th>
                    <th><?php echo fmt_currency($price_with_interest); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Kar Marjı (%)</th>
                    <th><?php echo fmt_currency($calculated_margin, 2); ?></th>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php if ($returnPrice && $groupedResults): ?>
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
