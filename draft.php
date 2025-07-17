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

$sql = "SELECT c.name AS Firma_Adi,
               cu.first_name AS Calisan_Adi,
               cu.last_name AS Calisan_Soyadi,
               cu.title AS Unvan,
               cu.email AS Email,
               cu.phone AS Telefon,
               cu.address AS Adres
        FROM companies c
        LEFT JOIN customers cu ON cu.company_id = c.id
        ORDER BY c.name, cu.first_name, cu.last_name";

$stmt = $pdo->query($sql);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firma ve Çalışanlar</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Firma ve Çalışanlar</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Firma Adı</th>
                <th>Çalışan Adı</th>
                <th>Çalışan Soyadı</th>
                <th>Ünvan</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Adres</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Firma_Adi']); ?></td>
                <td><?php echo htmlspecialchars($row['Calisan_Adi']); ?></td>
                <td><?php echo htmlspecialchars($row['Calisan_Soyadi']); ?></td>
                <td><?php echo htmlspecialchars($row['Unvan']); ?></td>
                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                <td><?php echo htmlspecialchars($row['Telefon']); ?></td>
                <td><?php echo htmlspecialchars($row['Adres']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
