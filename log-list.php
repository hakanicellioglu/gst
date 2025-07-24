<?php
// log-list.php - Audit log listing
// Connect to the database
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
load_theme_settings($pdo);

// Sanitize GET parameters
$table = isset($_GET['table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']) : '';
$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$logs = [];
if ($table && $recordId) {
    // Retrieve logs with user and action information
    $sql = "SELECT al.*, u.username, a.name AS action_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN actions a ON al.action_id = a.id
            WHERE al.table_name = :table_name AND al.record_id = :record_id
            ORDER BY al.action_time DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':table_name' => $table, ':record_id' => $recordId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
        <a href="javascript:history.back()" class="btn btn-secondary mb-3">Geri Dön</a>
        <?php if (!$table || !$recordId): ?>
        <div class="alert alert-warning">Gerekli parametreler bulunamadı.</div>
        <?php elseif (!$logs): ?>
        <div class="alert alert-warning">Kayıt bulunamadı.</div>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <?php
                    $old = json_decode($log['old_value'], true) ?: [];
                    $new = json_decode($log['new_value'], true) ?: [];
                    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                    $changes = [];
                    foreach ($keys as $k) {
                        $ov = $old[$k] ?? null;
                        $nv = $new[$k] ?? null;
                        if ($ov !== $nv) {
                            $changes[$k] = ['old' => $ov, 'new' => $nv];
                        }
                    }
                ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small"><?php echo htmlspecialchars($log['action_time']); ?></div>
                    <div>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($log['username'] ?? ''); ?></span>
                        <span
                            class="badge bg-info text-dark"><?php echo htmlspecialchars($log['action_name'] ?? ''); ?></span>
                    </div>
                </div>
                <?php if (!empty($log['description'])): ?>
                <p class="text-secondary mb-2"><?php echo htmlspecialchars($log['description']); ?></p>
                <?php endif; ?>
                <?php if ($changes): ?>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#log<?php echo $log['id']; ?>" aria-expanded="false"
                    aria-controls="log<?php echo $log['id']; ?>">Değişiklikleri Göster</button>
                <div class="collapse mt-2" id="log<?php echo $log['id']; ?>">
                    <?php foreach ($changes as $field => $diff): ?>
                    <div class="border-start border-3 ps-2 py-1 my-1">
                        <strong><?php echo htmlspecialchars($field); ?>:</strong>
                        <div>
                            <span class="text-danger">Eski:
                                <?php echo htmlspecialchars(var_export($diff['old'], true)); ?></span> <br>
                            <span class="text-success">Yeni:
                                <?php echo htmlspecialchars(var_export($diff['new'], true)); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>