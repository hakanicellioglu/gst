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
if (!is_admin($pdo)) {
    echo 'Bu sayfayı görüntüleme yetkiniz yok.';
    exit;
}
load_theme_settings($pdo);

$table = $_GET['table'] ?? '';
$recordId = $_GET['id'] ?? '';
$allowed = ['companies', 'customers', 'products', 'master_quotes'];
if (!in_array($table, $allowed, true) || $recordId === '') {
    echo 'Geçersiz istek';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT al.action_time,
            al.id,
            u.username,
            CONCAT(u.first_name, " ", u.last_name) AS full_name,
            al.old_value,
            al.new_value,
            a.name AS action_name
     FROM audit_logs al
     LEFT JOIN users u ON al.user_id = u.id
     LEFT JOIN actions a ON al.action_id = a.id
     WHERE al.table_name = :table AND al.record_id = :record
     ORDER BY al.action_time DESC'
);
$stmt->execute([
    ':table' => $table,
    ':record' => $recordId
]);
$logs = $stmt->fetchAll();

function format_log_value($value): string
{
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $parts = [];
        foreach ($decoded as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            $parts[] = htmlspecialchars($key) . ': ' . htmlspecialchars((string)$val);
        }
        return implode('<br>', $parts);
    }
    return htmlspecialchars($value);
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
        <h2 class="mb-4">Log Kayıtları</h2>
        <div class="row">
            <?php foreach ($logs as $log): ?>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($log['action_name']); ?></h5>
                            <p class="card-text">
                                Tarih ve Saat: <?php echo htmlspecialchars($log['action_time']); ?><br>
                                ID: <?php echo htmlspecialchars($log['id']); ?><br>
                                Kullanıcı: <?php echo htmlspecialchars($log['username']); ?><br>
                                İsim Soyisim: <?php echo htmlspecialchars($log['full_name']); ?><br>
                                Eski Değer: <?php echo format_log_value($log['old_value']); ?><br>
                                Yeni Değer: <?php echo format_log_value($log['new_value']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="javascript:history.back()" class="btn btn-secondary">Geri</a>
    </div>
</body>

</html>