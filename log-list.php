<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/auth.php';

// Start session and ensure user is authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

// Load theme/color preferences
load_theme_settings($pdo);

/**
 * Sanitize GET parameter value.
 */
function get_param(string $key): ?string
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}

$recordId  = get_param('record_id');
$tableName = get_param('table_name');

if (!$recordId || !$tableName) {
    // Missing parameters
    ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Kayıtları</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <div class="alert alert-warning">Kayıt bilgisi bulunamadı.</div>
</div>
</body>
</html>
<?php
    exit;
}

$recordId  = (int)$recordId; // sanitize numeric
$tableName = htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8');

/**
 * Fetch logs for a specific record.
 */
function fetch_logs(PDO $pdo, int $recordId, string $tableName): array
{
    $sql = "SELECT al.id, al.action_time, al.old_value, al.new_value, al.description,
                   u.username, ac.name AS action_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN actions ac ON al.action_id = ac.id
            WHERE al.record_id = :record_id AND al.table_name = :table_name
            ORDER BY al.action_time DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':record_id' => $recordId, ':table_name' => $tableName]);
    return $stmt->fetchAll();
}

/**
 * Compare JSON values and return changed fields.
 */
function diff_values(string $oldJson, string $newJson): array
{
    $old = json_decode($oldJson, true) ?: [];
    $new = json_decode($newJson, true) ?: [];
    $diff = [];
    foreach ($new as $key => $value) {
        $oldValue = $old[$key] ?? null;
        if ($oldValue !== $value) {
            $diff[] = [
                'field' => $key,
                'old'   => $oldValue,
                'new'   => $value,
            ];
        }
    }
    return $diff;
}

$logs = fetch_logs($pdo, $recordId, $tableName);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Kayıtları</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Log Kayıtları</h2>
    <?php if (empty($logs)): ?>
        <div class="alert alert-info">Bu kayıt için log bulunamadı.</div>
    <?php endif; ?>
    <?php foreach ($logs as $log): ?>
        <?php $changes = diff_values($log['old_value'], $log['new_value']); ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($log['action_time']); ?></span>
                <button class="btn btn-sm btn-outline-<?php echo get_color(); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#log<?php echo $log['id']; ?>" aria-expanded="false" aria-controls="log<?php echo $log['id']; ?>">
                    Detayları Gör
                </button>
            </div>
            <div class="card-body">
                <p class="mb-1">Kullanıcı: <strong><?php echo htmlspecialchars($log['username']); ?></strong></p>
                <p class="mb-1">İşlem: <strong><?php echo htmlspecialchars($log['action_name']); ?></strong></p>
                <p class="mb-2"><?php echo htmlspecialchars($log['description']); ?></p>
                <div class="collapse" id="log<?php echo $log['id']; ?>">
                    <?php if ($changes): ?>
                        <ul class="list-group">
                            <?php foreach ($changes as $change): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div><i class="bi bi-arrow-right-circle-fill text-<?php echo get_color(); ?> me-2"></i><?php echo htmlspecialchars($change['field']); ?></div>
                                    <div>
                                        <span class="text-muted me-1"><?php echo htmlspecialchars(var_export($change['old'], true)); ?></span>
                                        <i class="bi bi-arrow-right"></i>
                                        <span class="fw-bold ms-1"><?php echo htmlspecialchars(var_export($change['new'], true)); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted">Değişiklik yok</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
