<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $isim = trim($_POST['isim'] ?? '');
    $soyisim = trim($_POST['soyisim'] ?? '');
    $hitap = $_POST['hitap'] ?? 'bey';
    $eposta = trim($_POST['eposta'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $adres = trim($_POST['adres'] ?? '');

    if ($id) {
        $stmt = $conn->prepare('UPDATE yetkililer SET isim=?, soyisim=?, hitap=?, eposta=?, telefon=?, adres=? WHERE id=?');
        $stmt->bind_param('ssssssi', $isim, $soyisim, $hitap, $eposta, $telefon, $adres, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO yetkililer (firma_id, isim, soyisim, hitap, eposta, telefon, adres) VALUES (NULL,?,?,?,?,?,?)');
        $stmt->bind_param('ssssss', $isim, $soyisim, $hitap, $eposta, $telefon, $adres);
        $stmt->execute();
    }
    header('Location: auth');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM yetkililer WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: auth');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'id_desc';
$allowed = ['id_asc','id_desc','name_asc','name_desc'];
if (!in_array($sort, $allowed, true)) {
    $sort = 'id_desc';
}

switch ($sort) {
    case 'id_asc':
        $order = 'id ASC';
        break;
    case 'name_asc':
        $order = 'isim ASC';
        break;
    case 'name_desc':
        $order = 'isim DESC';
        break;
    default:
        $order = 'id DESC';
}

$sql = 'SELECT * FROM yetkililer';
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' WHERE isim LIKE ? OR soyisim LIKE ?';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
$sql .= ' ORDER BY ' . $order;

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteriler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container my-4">
    <div class="d-flex justify-content-between mb-3">
        <h1 class="h3">Müşteriler</h1>
        <div>
            <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">Filtrele / Sırala</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" id="addCustomerBtn">Müşteri Ekle</button>
        </div>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Ad</th>
                <th>Soyad</th>
                <th>Hitap</th>
                <th>Telefon</th>
                <th>Eposta</th>
                <th>Adres</th>
                <th class="text-end">İşlemler</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['isim']) ?></td>
                <td><?= htmlspecialchars($c['soyisim']) ?></td>
                <td><?= htmlspecialchars($c['hitap']) ?></td>
                <td><?= htmlspecialchars($c['telefon']) ?></td>
                <td><?= htmlspecialchars($c['eposta']) ?></td>
                <td><?= htmlspecialchars($c['adres']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-warning me-2 edit-btn" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['isim'], ENT_QUOTES) ?>" data-surname="<?= htmlspecialchars($c['soyisim'], ENT_QUOTES) ?>" data-hitap="<?= htmlspecialchars($c['hitap'], ENT_QUOTES) ?>" data-phone="<?= htmlspecialchars($c['telefon'], ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($c['eposta'], ENT_QUOTES) ?>" data-address="<?= htmlspecialchars($c['adres'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#customerModal">Düzenle</button>
                    <a href="auth?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="get" action="auth">
        <div class="modal-header">
          <h5 class="modal-title">Filtrele / Sırala</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">İsim veya Soyisim</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
          </div>
          <hr>
          <div class="mb-3">
            <label class="form-label">Sıralama</label>
            <select class="form-select" name="sort">
              <option value="id_desc" <?= $sort==='id_desc'? 'selected':'' ?>>ID (9-1)</option>
              <option value="id_asc" <?= $sort==='id_asc'? 'selected':'' ?>>ID (1-9)</option>
              <option value="name_asc" <?= $sort==='name_asc'? 'selected':'' ?>>A-Z</option>
              <option value="name_desc" <?= $sort==='name_desc'? 'selected':'' ?>>Z-A</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Uygula</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="auth" id="customerForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="customerId">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalLabel">Müşteri Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Müşteri Adı</label>
            <input type="text" class="form-control" name="isim" id="customerName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Müşteri Soyadı</label>
            <input type="text" class="form-control" name="soyisim" id="customerSurname">
          </div>
          <div class="mb-3">
            <label class="form-label">Hitap</label>
            <select class="form-select" name="hitap" id="customerHitap">
                <option value="bey">Bey</option>
                <option value="hanımefendi">Hanımefendi</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Eposta</label>
            <input type="email" class="form-control" name="eposta" id="customerEmail">
          </div>
          <div class="mb-3">
            <label class="form-label">Telefon</label>
            <input type="text" class="form-control" name="telefon" id="customerPhone">
          </div>
          <div class="mb-3">
            <label class="form-label">Adres</label>
            <input type="text" class="form-control" name="adres" id="customerAddress">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
<script>
const customerModal = document.getElementById('customerModal');
const customerId = document.getElementById('customerId');
const customerName = document.getElementById('customerName');
const customerSurname = document.getElementById('customerSurname');
const customerHitap = document.getElementById('customerHitap');
const customerEmail = document.getElementById('customerEmail');
const customerPhone = document.getElementById('customerPhone');
const customerAddress = document.getElementById('customerAddress');
const modalTitle = document.getElementById('customerModalLabel');

customerModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!button.classList.contains('edit-btn')) {
        modalTitle.textContent = 'Müşteri Ekle';
        customerId.value = '';
        customerName.value = '';
        customerSurname.value = '';
        customerHitap.value = 'bey';
        customerEmail.value = '';
        customerPhone.value = '';
        customerAddress.value = '';
        return;
    }
    modalTitle.textContent = 'Müşteri Düzenle';
    customerId.value = button.getAttribute('data-id');
    customerName.value = button.getAttribute('data-name');
    customerSurname.value = button.getAttribute('data-surname');
    customerHitap.value = button.getAttribute('data-hitap');
    customerEmail.value = button.getAttribute('data-email');
    customerPhone.value = button.getAttribute('data-phone');
    customerAddress.value = button.getAttribute('data-address');
});
</script>
</body>
</html>
