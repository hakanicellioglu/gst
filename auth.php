<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$editing = false;
$auth = [
    'id' => '',
    'firma_id' => '',
    'isim' => '',
    'soyisim' => '',
    'hitap' => 'bey',
    'eposta' => '',
    'telefon' => '',
    'adres' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $firma_id = (int)($_POST['firma_id'] ?? 0);
    $isim = trim($_POST['isim'] ?? '');
    $soyisim = trim($_POST['soyisim'] ?? '');
    $hitap = $_POST['hitap'] ?? 'bey';
    $eposta = trim($_POST['eposta'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $adres = trim($_POST['adres'] ?? '');

    if ($id) {
        $stmt = $conn->prepare('UPDATE yetkililer SET firma_id=?, isim=?, soyisim=?, hitap=?, eposta=?, telefon=?, adres=? WHERE id=?');
        $stmt->bind_param('issssssi', $firma_id, $isim, $soyisim, $hitap, $eposta, $telefon, $adres, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO yetkililer (firma_id, isim, soyisim, hitap, eposta, telefon, adres) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('issssss', $firma_id, $isim, $soyisim, $hitap, $eposta, $telefon, $adres);
        $stmt->execute();
    }
    header('Location: auth');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM yetkililer WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: auth');
    exit;
}

if (isset($_GET['edit'])) {
    $editing = true;
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM yetkililer WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $auth = $stmt->get_result()->fetch_assoc();
    if (!$auth) {
        $auth = [
            'id' => '',
            'firma_id' => '',
            'isim' => '',
            'soyisim' => '',
            'hitap' => 'bey',
            'eposta' => '',
            'telefon' => '',
            'adres' => ''
        ];
        $editing = false;
    }
}

$companies = $conn->query('SELECT id, firma_adi FROM firmalar ORDER BY firma_adi ASC')->fetch_all(MYSQLI_ASSOC);
$result = $conn->query('SELECT y.*, f.firma_adi FROM yetkililer y LEFT JOIN firmalar f ON y.firma_id = f.id ORDER BY y.id DESC');
$auths = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetkililer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Yetkililer</h1>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Firma</th>
                <th>Ad</th>
                <th>Soyad</th>
                <th>Hitap</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($auths as $a): ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['firma_adi']) ?></td>
                <td><?= htmlspecialchars($a['isim']) ?></td>
                <td><?= htmlspecialchars($a['soyisim']) ?></td>
                <td><?= htmlspecialchars($a['hitap']) ?></td>
                <td><?= htmlspecialchars($a['eposta']) ?></td>
                <td><?= htmlspecialchars($a['telefon']) ?></td>
                <td><?= htmlspecialchars($a['adres']) ?></td>
                <td>
                    <a href="auth?edit=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="auth?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <h2><?= $editing ? 'Yetkili Düzenle' : 'Yetkili Ekle' ?></h2>
    <form method="post" action="auth">
        <input type="hidden" name="id" value="<?= htmlspecialchars($auth['id']) ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Firma</label>
                <select name="firma_id" class="form-select" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $auth['firma_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['firma_adi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ad</label>
                <input type="text" name="isim" class="form-control" value="<?= htmlspecialchars($auth['isim']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Soyad</label>
                <input type="text" name="soyisim" class="form-control" value="<?= htmlspecialchars($auth['soyisim']) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Hitap</label>
                <select name="hitap" class="form-select">
                    <option value="bey" <?= $auth['hitap'] === 'bey' ? 'selected' : '' ?>>Bey</option>
                    <option value="hanımefendi" <?= $auth['hitap'] === 'hanımefendi' ? 'selected' : '' ?>>Hanımefendi</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="eposta" class="form-control" value="<?= htmlspecialchars($auth['eposta']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefon</label>
                <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($auth['telefon']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Adres</label>
                <input type="text" name="adres" class="form-control" value="<?= htmlspecialchars($auth['adres']) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Kaydet</button>
        <?php if ($editing): ?>
            <a href="auth" class="btn btn-secondary">İptal</a>
        <?php endif; ?>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
