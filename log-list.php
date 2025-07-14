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
            al.table_name,
            al.record_id,
            al.column_name,
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
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Tarih ve Saat</th>
                    <th>Kullanıcı</th>
                    <th>Tablo</th>
                    <th>Satır</th>
                    <th>Sütun</th>
                    <th>Eski Değer</th>
                    <th>Yeni Değer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['action_time']); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['column_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['old_value']); ?></td>
                        <td><?php echo htmlspecialchars($log['new_value']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="javascript:history.back()" class="btn btn-secondary">Geri</a>
    </div>
</body>

</html>