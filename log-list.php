<?php
// log-list.php - Audit log listing
// Connect to the database
require_once 'config.php';

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
    <title>Log Kayıtları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background:#f5f5f5; padding:1rem; }
        .container { max-width: 800px; margin:auto; }
        .card { background:#fff; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,0.1); padding:1rem; margin-bottom:1rem; }
        .header { font-size:0.9rem; color:#444; margin-bottom:0.5rem; }
        .meta { font-size:0.95rem; margin-bottom:0.5rem; }
        .meta span { margin-right:0.5rem; }
        .desc { color:#666; margin-bottom:0.5rem; }
        .btn { background:#0069d9; color:#fff; border:none; padding:0.3rem 0.6rem; border-radius:3px; cursor:pointer; font-size:0.85rem; }
        .changes { display:none; margin-top:0.5rem; }
        .change { background:#f8f8f8; border-left:3px solid #0069d9; padding:0.3rem; margin:0.2rem 0; }
        .field { font-weight:bold; }
        .old { color:#b21; margin-left:0.5rem; }
        .new { color:#1a7f37; margin-left:0.5rem; }
    </style>
</head>
<body>
<div class="container">
<?php if (!$table || !$recordId): ?>
    <div class="alert alert-warning mt-3">Gerekli parametreler bulunamadı.</div>
<?php elseif (!$logs): ?>
    <div class="alert alert-warning mt-3">Kayıt bulunamadı.</div>
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
        <div class="card">
            <div class="header"><?php echo htmlspecialchars($log['action_time']); ?></div>
            <div class="meta">
                <span><?php echo htmlspecialchars($log['username'] ?? ''); ?></span>
                <span><?php echo htmlspecialchars($log['action_name'] ?? ''); ?></span>
            </div>
            <?php if (!empty($log['description'])): ?>
                <div class="desc"><?php echo htmlspecialchars($log['description']); ?></div>
            <?php endif; ?>
            <?php if ($changes): ?>
                <button class="btn" onclick="toggle('c<?php echo $log['id']; ?>')">Değişiklikleri Göster</button>
                <div class="changes" id="c<?php echo $log['id']; ?>">
                    <?php foreach ($changes as $field => $diff): ?>
                        <div class="change">
                            <span class="field"><?php echo htmlspecialchars($field); ?>:</span>
                            <span class="old">- <?php echo htmlspecialchars(var_export($diff['old'], true)); ?></span>
                            <span class="new">+ <?php echo htmlspecialchars(var_export($diff['new'], true)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<script>
function toggle(id){
    var e=document.getElementById(id);
    e.style.display = (e.style.display==='block')? 'none' : 'block';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
