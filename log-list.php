<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}
load_theme_settings($pdo);

$table = isset($_GET['table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']) : '';
$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($table && $recordId) {
$stmt = $pdo->prepare('SELECT al.*, u.username, a.name AS action_name 
                       FROM audit_logs al 
                       LEFT JOIN users u ON al.user_id = u.id 
                       LEFT JOIN actions a ON al.action_id = a.id 
                       WHERE al.table_name = :table AND al.record_id = :id 
                       ORDER BY al.action_time DESC');
    $stmt->execute([':table' => $table, ':id' => $recordId]);
    $logs = $stmt->fetchAll();
} else {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Kayıtları</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:0; }
        .container { max-width: 800px; margin:2rem auto; padding:0 1rem; }
        .log-card { background:#fff; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1); padding:1rem; margin-bottom:1rem; }
        .log-header { font-size:0.9rem; color:#555; margin-bottom:0.5rem; }
        .log-meta { font-size:0.95rem; margin-bottom:0.5rem; }
        .log-meta span { margin-right:0.5rem; }
        .log-desc { color:#666; margin-bottom:0.5rem; }
        .toggle-btn { background:#007BFF; color:#fff; border:none; padding:0.4rem 0.7rem; border-radius:4px; cursor:pointer; font-size:0.9rem; }
        .changes { display:none; margin-top:0.5rem; }
        .change { background:#fafafa; border-left:4px solid #007BFF; padding:0.4rem; margin:0.3rem 0; }
        .field { font-weight:bold; }
        .old { color:#b21; margin-left:0.5rem; }
        .new { color:#1a7f37; margin-left:0.5rem; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!$table || !$recordId): ?>
        <p>Gerekli parametreler bulunamadı.</p>
    <?php elseif (!$logs): ?>
        <p>Kayıt bulunamadı.</p>
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
            <div class="log-card">
                <div class="log-header"><?php echo htmlspecialchars($log['action_time']); ?></div>
                <div class="log-meta">
                    <span><?php echo htmlspecialchars($log['username'] ?? ''); ?></span>
                    <span><?php echo htmlspecialchars($log['action_name'] ?? ''); ?></span>
                </div>
                <?php if (!empty($log['description'])): ?>
                    <div class="log-desc"><?php echo htmlspecialchars($log['description']); ?></div>
                <?php endif; ?>
                <?php if ($changes): ?>
                    <button class="toggle-btn" onclick="toggleChanges('c<?php echo $log['id']; ?>')">Değişiklikleri Göster</button>
                    <div class="changes" id="c<?php echo $log['id']; ?>">
                        <?php foreach ($changes as $field => $diff): ?>
                            <div class="change">
                                <span class="field"><?php echo htmlspecialchars($field); ?>:</span>
                                <span class="old"><?php echo htmlspecialchars(var_export($diff['old'], true)); ?></span>
                                <span class="new">→ <?php echo htmlspecialchars(var_export($diff['new'], true)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
function toggleChanges(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none' || !el.style.display) {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>
</body>
</html>
