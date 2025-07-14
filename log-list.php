<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/auth.php';

// Start session and ensure authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

// Load user theme preferences
load_theme_settings($pdo);

/**
 * Sanitize query parameter
 */
function get_param(string $key): ?string
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}

// Retrieve and sanitize filter values
$username = filter_var(get_param('username'), FILTER_SANITIZE_STRING) ?? '';
$sort     = strtolower(get_param('sort') ?? 'desc');
$sort     = $sort === 'asc' ? 'ASC' : 'DESC';

// Build SQL query
$sql = "SELECT al.id, al.user_id, u.username, al.action_time, al.table_name,
               al.record_id, al.column_name, al.old_value, al.new_value,
               al.description, al.action_id, ac.name AS action_name
        FROM audit_logs al
        LEFT JOIN users u   ON al.user_id = u.id
        LEFT JOIN actions ac ON al.action_id = ac.id";
$params = [];
$conditions = [];

if ($username !== '') {
    $conditions[] = 'u.username LIKE :username';
    $params[':username'] = "%{$username}%";
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Log Kayıtları</h2>
    <form method="get" class="row gy-2 gx-3 align-items-center mb-4">
        <div class="col-sm-4">
            <input type="text" class="form-control" name="username" placeholder="Kullanıcı ara" value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="col-sm-3">
            <select name="sort" class="form-select">
                <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Yeni Tarih</option>
                <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>Eski Tarih</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Filtrele</button>
        </div>
    </form>
    <?php if (empty($logs)): ?>
        <div class="alert alert-info">Kayıt bulunamadı.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
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
    <?php endif; ?>
</div>
</body>
</html>
