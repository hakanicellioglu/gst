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

// Sanitize inputs
$username = trim($_GET['username'] ?? '');
$sortParam = strtolower($_GET['sort'] ?? 'desc');
$sort = $sortParam === 'asc' ? 'ASC' : 'DESC';

// Build query with optional username filter
$sql = 'SELECT al.id, u.username, al.action_time, al.table_name, al.record_id,
               al.column_name, al.old_value, al.new_value, al.description,
               act.name AS action_name
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN actions act ON al.action_id = act.id
        WHERE 1';
$params = [];
if ($username !== '') {
    $sql .= ' AND u.username LIKE :username';
    $params[':username'] = "%" . $username . "%";
}
$sql .= " ORDER BY al.action_time $sort";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
    <form method="get" class="row gy-2 gx-3 align-items-center mb-3">
        <div class="col-auto">
            <input type="text" name="username" class="form-control" placeholder="Kullanıcı ara"
                   value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="col-auto">
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Yeni &rsaquo; Eski</option>
                <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>Eski &rsaquo; Yeni</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Filtrele</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Tarih</th>
                    <th>Tablo</th>
                    <th>Kayıt ID</th>
                    <th>Sütun</th>
                    <th>Eski Değer</th>
                    <th>Yeni Değer</th>
                    <th>Açıklama</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                    <td><?php echo htmlspecialchars($log['username'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($log['action_time']); ?></td>
                    <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                    <td><?php echo htmlspecialchars($log['column_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['old_value']); ?></td>
                    <td><?php echo htmlspecialchars($log['new_value']); ?></td>
                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                    <td><?php echo htmlspecialchars($log['action_name']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
