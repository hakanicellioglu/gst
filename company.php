<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Add company
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('INSERT INTO musteriler (firma_adi, yetkili_adi, telefon, adres, eposta) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $_POST['firma_adi'], $_POST['yetkili_adi'], $_POST['telefon'], $_POST['adres'], $_POST['eposta']);
    $stmt->execute();
    header('Location: company');
    exit;
}

// Update company
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('UPDATE musteriler SET firma_adi=?, yetkili_adi=?, telefon=?, adres=?, eposta=? WHERE id=?');
    $stmt->bind_param('sssssi', $_POST['firma_adi'], $_POST['yetkili_adi'], $_POST['telefon'], $_POST['adres'], $_POST['eposta'], $id);
    $stmt->execute();
    header('Location: company');
    exit;
}

// Delete company
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare('DELETE FROM musteriler WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: company');
    exit;
}

// Fetch company for editing
$editCompany = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare('SELECT * FROM musteriler WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editCompany = $stmt->get_result()->fetch_assoc();
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'firma_adi';
$order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$sql = 'SELECT * FROM musteriler';
$params = [];
$types = '';
if ($search) {
    $sql .= ' WHERE firma_adi LIKE ? OR yetkili_adi LIKE ?';
    $like = "%{$search}%";
    $params[] = &$like;
    $params[] = &$like;
    $types = 'ss';
}
$sql .= " ORDER BY {$sort} {$order}";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmalar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Firmalar</h1>

    <form class="row mb-4" method="get" action="company">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Ara" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="firma_adi" <?= $sort === 'firma_adi' ? 'selected' : '' ?>>Firma Adı</option>
                <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Oluşturma Tarihi</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="order" class="form-select">
                <option value="asc" <?= $order === 'ASC' ? 'selected' : '' ?>>Artan</option>
                <option value="desc" <?= $order === 'DESC' ? 'selected' : '' ?>>Azalan</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Firma Adı</th>
                <th>Yetkili Adı</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th>Eposta</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['firma_adi']) ?></td>
                <td><?= htmlspecialchars($c['yetkili_adi']) ?></td>
                <td><?= htmlspecialchars($c['telefon']) ?></td>
                <td><?= htmlspecialchars($c['adres']) ?></td>
                <td><?= htmlspecialchars($c['eposta']) ?></td>
                <td>
                    <a href="company?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="company?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
    <h2><?= $editCompany ? 'Firma Düzenle' : 'Firma Ekle' ?></h2>
    <form method="post" action="company?action=<?= $editCompany ? 'edit&id=' . $editCompany['id'] : 'add' ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Firma Adı</label>
                <input type="text" name="firma_adi" class="form-control" value="<?= htmlspecialchars($editCompany['firma_adi'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Yetkili Adı</label>
                <input type="text" name="yetkili_adi" class="form-control" value="<?= htmlspecialchars($editCompany['yetkili_adi'] ?? '') ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Telefon</label>
                <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($editCompany['telefon'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Eposta</label>
                <input type="email" name="eposta" class="form-control" value="<?= htmlspecialchars($editCompany['eposta'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Adres</label>
                <input type="text" name="adres" class="form-control" value="<?= htmlspecialchars($editCompany['adres'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Kaydet</button>
        <?php if ($editCompany): ?>
            <a href="company" class="btn btn-secondary">İptal</a>
        <?php endif; ?>
    </form>
</div>
<script>
console.log('Sayfa yüklendi. editCompany durumu:', <?= json_encode((bool)$editCompany) ?>);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>