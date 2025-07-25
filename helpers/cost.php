<?php
// Helper for material cost calculations for guillotine quotes

// Fixed aluminum price per kilogram
const ALUMINUM_PRICE_PER_KG = 190.0;

/**
 * Calculate material cost for a guillotine system.
 *
 * @param PDO   $pdo    Database connection
 * @param array $quote  Quote data including width_mm, height_mm, system_qty,
 *                      glass_type and system_type
 * @return float Total cost in TRY
 */
function calculate_guillotine_material_cost(PDO $pdo, array $quote): float
{
    $width  = (float)($quote['width_mm'] ?? 0);
    $height = (float)($quote['height_mm'] ?? 0);
    $qty    = max(1, (int)($quote['system_qty'] ?? 1));
    $glass  = $quote['glass_type'] ?? '';
    $type   = $quote['system_type'] ?? 'Giyotin';

    if ($width <= 0 || $height <= 0) {
        return 0.0;
    }

    // Fetch glass unit price from products table
    $stmt = $pdo->prepare('SELECT unit_price FROM products WHERE name = ? LIMIT 1');
    $stmt->execute([$glass]);
    $glassPrice = (float)$stmt->fetchColumn();
    if ($glassPrice <= 0) {
        $glassPrice = 0.0;
    }

    $area = ($width / 1000) * ($height / 1000); // m²
    $glassCost = $area * $glassPrice * $qty;

    // Predefined aluminum weight per m² for different system types
    $weightMap = [
        'Giyotin' => 10.0,
    ];
    $weightPerSqm = $weightMap[$type] ?? $weightMap['Giyotin'];
    $alWeight = $area * $weightPerSqm * $qty;
    $alCost = $alWeight * ALUMINUM_PRICE_PER_KG;

    return round($glassCost + $alCost, 2);
}
?>
