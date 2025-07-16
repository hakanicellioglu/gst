<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !is_admin($pdo)) {
    header('Location: /login');
    exit;
}

load_theme_settings($pdo);

// Basic stats for dashboard
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orderCount = $pdo->query("SELECT COUNT(*) FROM master_quotes")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM master_quotes")->fetchColumn();
$revenue = $revenue ?: 0;

$recentUsersStmt = $pdo->query(
    "SELECT u.username, r.name AS role, u.created_at\n".
    "FROM users u\n".
    "LEFT JOIN role_user ru ON u.id = ru.user_id\n".
    "LEFT JOIN roles r ON ru.role_id = r.id\n".
    "ORDER BY u.created_at DESC LIMIT 5"
);
$recentUsers = $recentUsersStmt->fetchAll();

// Revenue for the last 7 days
$revenueDaysStmt = $pdo->query(
    "SELECT DATE(quote_date) AS dt, SUM(total_amount) AS total\n".
    "FROM master_quotes\n".
    "WHERE quote_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)\n".
    "GROUP BY DATE(quote_date)\n".
    "ORDER BY dt"
);
$revenueDays = $revenueDaysStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$lineLabels = [];
$lineValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $lineLabels[] = date('d.m', strtotime($d));
    $lineValues[] = isset($revenueDays[$d]) ? (float)$revenueDays[$d] : 0;
}

// Product count by category
$categoryStmt = $pdo->query(
    "SELECT category, COUNT(*) AS total FROM products GROUP BY category ORDER BY category"
);
$categoryData = $categoryStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$pieLabels = array_keys($categoryData);
$pieValues = array_values($categoryData);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetici Paneli</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-body-tertiary sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="#">Gösterge Paneli</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Kullanıcılar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Roller & Yetkiler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Siparişler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Ürünler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Raporlama</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Bildirimler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Ayarlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Destek Talepleri</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Log Kayıtları</a></li>
                </ul>
            </div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h2 mb-4 text-<?php echo get_color(); ?>">Yönetici Paneli</h1>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Kullanıcı</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo htmlspecialchars($userCount); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Ürün</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo htmlspecialchars($productCount); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Sipariş</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo htmlspecialchars($orderCount); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Gelir</h5>
                            <p class="display-6 fw-bold mb-0">₺<?php echo number_format($revenue, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-lg-6">
                    <canvas id="lineChart" height="200"></canvas>
                </div>
                <div class="col-lg-6">
                    <canvas id="pieChart" height="200"></canvas>
                </div>
            </div>
            <div class="mb-4">
                <a href="product.php" class="btn btn-success me-2">Ürün Ekle</a>
                <a href="offer.php" class="btn btn-warning me-2">Teklif Oluştur</a>
                <a href="company.php" class="btn btn-primary">Firma Ekle</a>
            </div>
            <h2 class="h4 mt-5">Son Kullanıcılar</h2>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Kullanıcı Adı</th>
                            <th>Rol</th>
                            <th>Kayıt Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['role'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
const lineCtx = document.getElementById('lineChart');
const lineLabels = <?php echo json_encode($lineLabels); ?>;
const lineData = <?php echo json_encode($lineValues); ?>;
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: lineLabels,
        datasets: [{
            label: 'Gelir',
            data: lineData,
            borderColor: '#0d6efd',
            fill: false
        }]
    }
});
const pieCtx = document.getElementById('pieChart');
const pieLabels = <?php echo json_encode($pieLabels); ?>;
const pieData = <?php echo json_encode($pieValues); ?>;
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: pieLabels,
        datasets: [{
            data: pieData,
            backgroundColor: ['#0d6efd','#198754','#dc3545','#6c757d','#6610f2']
        }]
    }
});
</script>
</body>
</html>
