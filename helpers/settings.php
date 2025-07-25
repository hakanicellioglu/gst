<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_DEFAULT_SETTINGS = [
    'order_email'           => 'orders@example.com',
    'currency'              => 'USD',
    'timezone'              => 'UTC',
    'date_format'           => 'Y-m-d H:i',
    // Pricing related defaults
    'aluminum_cost_per_kg'  => 202.77,
    'glass_cost_per_sqm'    => 1295.26,
    'monthly_interest_rate' => 0.05,
];

function get_setting(PDO $pdo, string $key)
{
    $userId = $_SESSION['user']['id'] ?? null;
    static $cache = [];
    $cacheKey = ($userId ?? 'default') . ':' . $key;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare('SELECT value FROM settings WHERE user_id = ? AND `key` = ?');
            $stmt->execute([$userId, $key]);
            $val = $stmt->fetchColumn();
            if ($val !== false) {
                $val = json_decode($val, true);
                $cache[$cacheKey] = $val;
                return $val;
            }
        } catch (PDOException $e) {
            // ignore errors
        }
    }
    $default = APP_DEFAULT_SETTINGS[$key] ?? null;
    $cache[$cacheKey] = $default;
    return $default;
}

function save_user_setting(PDO $pdo, string $key, $value): bool
{
    $userId = $_SESSION['user']['id'] ?? null;
    if ($userId === null) {
        return false;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO settings (user_id, `key`, value) ' .
            'VALUES (:user, :key, :val) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([
            ':user' => $userId,
            ':key'  => $key,
            ':val'  => json_encode($value)
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
