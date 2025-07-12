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

$table = $_GET['table'] ?? '';
$recordId = $_GET['id'] ?? '';
$allowed = ['companies', 'customers', 'products', 'master_quotes'];
if (!in_array($table, $allowed, true) || $recordId === '') {
    echo 'Geçersiz istek';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT al.action_time, al.id, u.username, CONCAT(u.first_name, " ", u.last_name) AS full_name
     FROM audit_logs al
     LEFT JOIN users u ON al.user_id = u.id
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
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>İsim Soyisim</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['action_time']); ?></td>
                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="javascript:history.back()" class="btn btn-secondary">Geri</a>
    </div>
</body>
</html>
