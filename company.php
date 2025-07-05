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
    $firma_adi = trim($_POST['firma_adi'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $adres = trim($_POST['adres'] ?? '');

    if ($id) {
        $stmt = $conn->prepare('UPDATE firmalar SET firma_adi=?, telefon=?, adres=? WHERE id=?');
        $stmt->bind_param('sssi', $firma_adi, $telefon, $adres, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO firmalar (firma_adi, telefon, adres) VALUES (?,?,?)');
        $stmt->bind_param('sss', $firma_adi, $telefon, $adres);
        $stmt->execute();
    }
    header('Location: company');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM firmalar WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: company');
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
        $order = 'firma_adi ASC';
        break;
    case 'name_desc':
        $order = 'firma_adi DESC';
        break;
    default:
        $order = 'id DESC';
}

$sql = 'SELECT * FROM firmalar';
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' WHERE firma_adi LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}
$sql .= ' ORDER BY ' . $order;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<div class="container my-4">
    <div class="d-flex justify-content-between mb-3">
        <h1 class="h3">Firmalar</h1>
        <div>
            <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">Filtrele / Sırala</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal" id="addCompanyBtn">Firma Ekle</button>
        </div>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Firma Adı</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th class="text-end">İşlemler</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($companies as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['firma_adi']) ?></td>
                <td><?= htmlspecialchars($c['telefon']) ?></td>
                <td><?= htmlspecialchars($c['adres']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-warning me-2 edit-btn" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['firma_adi'], ENT_QUOTES) ?>" data-phone="<?= htmlspecialchars($c['telefon'], ENT_QUOTES) ?>" data-address="<?= htmlspecialchars($c['adres'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#companyModal">Düzenle</button>
                    <a href="company?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
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
      <form method="get" action="company">
        <div class="modal-header">
          <h5 class="modal-title">Filtrele / Sırala</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Firma Adı</label>
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
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="company" id="companyForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="companyId">
        <div class="modal-header">
          <h5 class="modal-title" id="companyModalLabel">Firma Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Firma Adı</label>
            <input type="text" class="form-control" name="firma_adi" id="companyName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Telefon</label>
            <input type="text" class="form-control" name="telefon" id="companyPhone">
          </div>
          <div class="mb-3">
            <label class="form-label">Adres</label>
            <input type="text" class="form-control" name="adres" id="companyAddress">
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
const companyModal = document.getElementById('companyModal');
const companyId = document.getElementById('companyId');
const companyName = document.getElementById('companyName');
const companyPhone = document.getElementById('companyPhone');
const companyAddress = document.getElementById('companyAddress');
const modalTitle = document.getElementById('companyModalLabel');

companyModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!button.classList.contains('edit-btn')) {
        modalTitle.textContent = 'Firma Ekle';
        companyId.value = '';
        companyName.value = '';
        companyPhone.value = '';
        companyAddress.value = '';
        return;
    }
    modalTitle.textContent = 'Firma Düzenle';
    companyId.value = button.getAttribute('data-id');
    companyName.value = button.getAttribute('data-name');
    companyPhone.value = button.getAttribute('data-phone');
    companyAddress.value = button.getAttribute('data-address');
});
</script>
</body>
</html>
