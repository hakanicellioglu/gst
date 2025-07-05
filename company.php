<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$editing = false;
$company = ['id' => '', 'firma_adi' => '', 'eposta' => '', 'telefon' => '', 'adres' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $firma_adi = trim($_POST['firma_adi'] ?? '');
    $eposta = trim($_POST['eposta'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $adres = trim($_POST['adres'] ?? '');

    if ($id) {
        $stmt = $conn->prepare('UPDATE firmalar SET firma_adi=?, eposta=?, telefon=?, adres=? WHERE id=?');
        $stmt->bind_param('ssssi', $firma_adi, $eposta, $telefon, $adres, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO firmalar (firma_adi, eposta, telefon, adres) VALUES (?,?,?,?)');
        $stmt->bind_param('ssss', $firma_adi, $eposta, $telefon, $adres);
        $stmt->execute();
    }
    header('Location: company');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM firmalar WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: company');
    exit;
}

if (isset($_GET['edit'])) {
    $editing = true;
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM firmalar WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    if (!$company) {
        $company = ['id' => '', 'firma_adi' => '', 'eposta' => '', 'telefon' => '', 'adres' => ''];
        $editing = false;
    }
}

$result = $conn->query('SELECT * FROM firmalar ORDER BY id DESC');
$companies = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmalar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Firmalar</h1>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Firma Adı</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['firma_adi']) ?></td>
                <td><?= htmlspecialchars($c['eposta']) ?></td>
                <td><?= htmlspecialchars($c['telefon']) ?></td>
                <td><?= htmlspecialchars($c['adres']) ?></td>
                <td>
                    <a href="company?edit=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="company?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <h2><?= $editing ? 'Firma Düzenle' : 'Firma Ekle' ?></h2>
    <form method="post" action="company">
        <input type="hidden" name="id" value="<?= htmlspecialchars($company['id']) ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Firma Adı</label>
                <input type="text" name="firma_adi" class="form-control" value="<?= htmlspecialchars($company['firma_adi']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="eposta" class="form-control" value="<?= htmlspecialchars($company['eposta']) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Telefon</label>
                <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($company['telefon']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Adres</label>
                <input type="text" name="adres" class="form-control" value="<?= htmlspecialchars($company['adres']) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Kaydet</button>
        <?php if ($editing): ?>
            <a href="company" class="btn btn-secondary">İptal</a>
        <?php endif; ?>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
