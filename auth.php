<?php
session_start();
require 'config.php';

$message = $_SESSION['message'] ?? '';
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Add user
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO kullanicilar (isim, kullanici_adi, parola, eposta, rol_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssi', $_POST['isim'], $_POST['kullanici_adi'], $hashed, $_POST['eposta'], $_POST['rol_id']);
    $stmt->execute();
    $_SESSION['message'] = 'Kullan\xC4\xB1c\xC4\xB1 eklendi.';
    header('Location: auth');
    exit;
}

// Update user
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['password'])) {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE kullanicilar SET isim=?, kullanici_adi=?, eposta=?, rol_id=?, parola=? WHERE id=?');
        $stmt->bind_param('sssssi', $_POST['isim'], $_POST['kullanici_adi'], $_POST['eposta'], $_POST['rol_id'], $hashed, $id);
    } else {
        $stmt = $conn->prepare('UPDATE kullanicilar SET isim=?, kullanici_adi=?, eposta=?, rol_id=? WHERE id=?');
        $stmt->bind_param('ssssi', $_POST['isim'], $_POST['kullanici_adi'], $_POST['eposta'], $_POST['rol_id'], $id);
    }
    $stmt->execute();
    $_SESSION['message'] = 'Kullan\xC4\xB1c\xC4\xB1 g\xC3\xBCncellendi.';
    header('Location: auth');
    exit;
}

// Delete user
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare('DELETE FROM kullanicilar WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $_SESSION['message'] = 'Kullan\xC4\xB1c\xC4\xB1 silindi.';
    header('Location: auth');
    exit;
}

// Fetch user for editing
$editUser = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare('SELECT * FROM kullanicilar WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'kullanici_adi';
$order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$sql = 'SELECT k.*, r.rol_adi FROM kullanicilar k LEFT JOIN roller r ON k.rol_id = r.id';
$params = [];
$types = '';
if ($search) {
    $sql .= ' WHERE k.isim LIKE ? OR k.kullanici_adi LIKE ? OR k.eposta LIKE ?';
    $like = "%{$search}%";
    $params[] = &$like;
    $params[] = &$like;
    $params[] = &$like;
    $types = 'sss';
}
$sql .= " ORDER BY {$sort} {$order}";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$rolesStmt = $conn->query('SELECT id, rol_adi FROM roller');
$roles = $rolesStmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullan\xC4\xB1c\xC4\xB1lar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Kullan\xC4\xB1c\xC4\xB1lar</h1>
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form class="row mb-4" method="get" action="auth">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Ara" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="kullanici_adi" <?= $sort === 'kullanici_adi' ? 'selected' : '' ?>>Kullan\xC4\xB1c\xC4\xB1 Ad\xC4\xB1</option>
                <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Olu\xC5\x9Fturma Tarihi</option>
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

    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">Kullan\xC4\xB1c\xC4\xB1 Ekle</button>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Ad</th>
                <th>Kullan\xC4\xB1c\xC4\xB1 Ad\xC4\xB1</th>
                <th>Email</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['isim']) ?></td>
                    <td><?= htmlspecialchars($u['kullanici_adi']) ?></td>
                    <td><?= htmlspecialchars($u['eposta']) ?></td>
                    <td><?= htmlspecialchars($u['rol_adi']) ?></td>
                    <td>
                        <a href="auth?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                        <a href="auth?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="auth?action=<?= $editUser ? 'edit&id=' . $editUser['id'] : 'add' ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">
                            <?= $editUser ? 'Kullan\xC4\xB1c\xC4\xB1 Düzenle' : 'Kullan\xC4\xB1c\xC4\xB1 Ekle' ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="isim" class="form-control" value="<?= htmlspecialchars($editUser['isim'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kullan\xC4\xB1c\xC4\xB1 Ad\xC4\xB1</label>
                            <input type="text" name="kullanici_adi" class="form-control" value="<?= htmlspecialchars($editUser['kullanici_adi'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="eposta" class="form-control" value="<?= htmlspecialchars($editUser['eposta'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parola<?php if(!$editUser): ?><?php endif; ?></label>
                            <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol_id" class="form-select">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= isset($editUser['rol_id']) && $editUser['rol_id'] == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['rol_adi']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Kaydet</button>
                        <?php if ($editUser): ?>
                            <a href="auth" class="btn btn-secondary">İptal</a>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editing = <?= json_encode((bool) $editUser) ?>;
        if (editing) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>