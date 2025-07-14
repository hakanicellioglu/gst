<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/audit.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}
load_theme_settings($pdo);

// Load companies and customers
$companyStmt = $pdo->query("SELECT id, name FROM companies ORDER BY name");
$companies = $companyStmt->fetchAll();
$customerStmt = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM customers ORDER BY first_name, last_name");
$customers = $customerStmt->fetchAll();
$canAdd = count($companies) > 0 && count($customers) > 0;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$quote = null;
$guillotines = [];
$slidings = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM master_quotes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $quote = $stmt->fetch();
    if (!$quote) {
        header('Location: offer');
        exit;
    }

    // Fetch related guillotine and sliding quotes
    $gStmt = $pdo->prepare("SELECT * FROM guillotine_quotes WHERE master_quote_id = :id");
    $gStmt->execute([':id' => $id]);
    $guillotines = $gStmt->fetchAll();

    $sStmt = $pdo->prepare("SELECT * FROM sliding_quotes WHERE master_quote_id = :id");
    $sStmt->execute([':id' => $id]);
    $slidings = $sStmt->fetchAll();
}

// Handle adding guillotine quote rows
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guillotine']) && $id) {
    $stmt = $pdo->prepare(
        "INSERT INTO guillotine_quotes (master_quote_id, system_type, width_mm, height_mm, system_qty, glass_type, glass_color, motor_system, remote_qty, ral_code) " .
        "VALUES (:master, 'Giyotin', :width, :height, :qty, :glass, :color, :motor, :remote_qty, :ral)"
    );
    $stmt->execute([
        ':master' => $id,
        ':width' => $_POST['width_mm'],
        ':height' => $_POST['height_mm'],
        ':qty' => $_POST['system_qty'],
        ':glass' => $_POST['glass_type'],
        ':color' => $_POST['glass_color'],
        ':motor' => $_POST['motor_system'],
        ':remote_qty' => $_POST['remote_qty'],
        ':ral' => $_POST['ral_code']
    ]);
    $newId = $pdo->lastInsertId();
    $stmtData = $pdo->prepare('SELECT * FROM guillotine_quotes WHERE id=:id');
    $stmtData->execute([':id' => $newId]);
    $newData = $stmtData->fetch();
    audit_log($pdo, 'guillotine_quotes', $newId, 'create', null, $newData);
    header('Location: offer_form?id=' . $id);
    exit;
}

// Handle adding sliding quote rows
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sliding']) && $id) {
    $stmt = $pdo->prepare(
        "INSERT INTO sliding_quotes (master_quote_id, system_type, width_mm, height_mm, wing_type, fastening_type, glass_type, glass_color, system_qty, ral_code, locking) " .
        "VALUES (:master, :system, :width, :height, :wing, :fastening, :glass, :color, :qty, :ral, :locking)"
    );
    $stmt->execute([
        ':master' => $id,
        ':system' => $_POST['system_type'],
        ':width' => $_POST['width_mm'],
        ':height' => $_POST['height_mm'],
        ':wing' => $_POST['wing_type'],
        ':fastening' => $_POST['fastening_type'],
        ':glass' => $_POST['glass_type'],
        ':color' => $_POST['glass_color'],
        ':qty' => $_POST['system_qty'],
        ':ral' => $_POST['ral_code'],
        ':locking' => $_POST['locking']
    ]);
    $newId = $pdo->lastInsertId();
    $stmtData = $pdo->prepare('SELECT * FROM sliding_quotes WHERE id=:id');
    $stmtData->execute([':id' => $newId]);
    $newData = $stmtData->fetch();
    audit_log($pdo, 'sliding_quotes', $newId, 'create', null, $newData);
    header('Location: offer_form?id=' . $id);
    exit;
}

// Handle editing guillotine quotes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_guillotine']) && $id) {
    $stmtOld = $pdo->prepare('SELECT * FROM guillotine_quotes WHERE id=:gid');
    $stmtOld->execute([':gid' => $_POST['gid']]);
    $oldData = $stmtOld->fetch();

    $stmt = $pdo->prepare(
        "UPDATE guillotine_quotes SET width_mm=:width, height_mm=:height, system_qty=:qty, glass_type=:glass, glass_color=:color, motor_system=:motor, remote_qty=:remote_qty, ral_code=:ral WHERE id=:gid AND master_quote_id=:master"
    );
    $stmt->execute([
        ':width' => $_POST['width_mm'],
        ':height' => $_POST['height_mm'],
        ':qty' => $_POST['system_qty'],
        ':glass' => $_POST['glass_type'],
        ':color' => $_POST['glass_color'],
        ':motor' => $_POST['motor_system'],
        ':remote_qty' => $_POST['remote_qty'],
        ':ral' => $_POST['ral_code'],
        ':gid' => $_POST['gid'],
        ':master' => $id
    ]);
    $stmtNew = $pdo->prepare('SELECT * FROM guillotine_quotes WHERE id=:gid');
    $stmtNew->execute([':gid' => $_POST['gid']]);
    $newData = $stmtNew->fetch();
    audit_log($pdo, 'guillotine_quotes', $_POST['gid'], 'update', $oldData, $newData);
    header('Location: offer_form?id=' . $id);
    exit;
}

// Handle deleting guillotine quotes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_guillotine']) && $id) {
    $stmtOld = $pdo->prepare('SELECT * FROM guillotine_quotes WHERE id=:gid');
    $stmtOld->execute([':gid' => $_POST['gid']]);
    $oldData = $stmtOld->fetch();

    $stmt = $pdo->prepare("DELETE FROM guillotine_quotes WHERE id=:gid AND master_quote_id=:master");
    $stmt->execute([
        ':gid' => $_POST['gid'],
        ':master' => $id
    ]);
    audit_log($pdo, 'guillotine_quotes', $_POST['gid'], 'delete', $oldData, null);
    header('Location: offer_form?id=' . $id);
    exit;
}

// Handle editing sliding quotes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sliding']) && $id) {
    $stmtOld = $pdo->prepare('SELECT * FROM sliding_quotes WHERE id=:sid');
    $stmtOld->execute([':sid' => $_POST['sid']]);
    $oldData = $stmtOld->fetch();

    $stmt = $pdo->prepare(
        "UPDATE sliding_quotes SET system_type=:system, width_mm=:width, height_mm=:height, wing_type=:wing, fastening_type=:fastening, glass_type=:glass, glass_color=:color, system_qty=:qty, ral_code=:ral, locking=:locking WHERE id=:sid AND master_quote_id=:master"
    );
    $stmt->execute([
        ':system' => $_POST['system_type'],
        ':width' => $_POST['width_mm'],
        ':height' => $_POST['height_mm'],
        ':wing' => $_POST['wing_type'],
        ':fastening' => $_POST['fastening_type'],
        ':glass' => $_POST['glass_type'],
        ':color' => $_POST['glass_color'],
        ':qty' => $_POST['system_qty'],
        ':ral' => $_POST['ral_code'],
        ':locking' => $_POST['locking'],
        ':sid' => $_POST['sid'],
        ':master' => $id
    ]);
    $stmtNew = $pdo->prepare('SELECT * FROM sliding_quotes WHERE id=:sid');
    $stmtNew->execute([':sid' => $_POST['sid']]);
    $newData = $stmtNew->fetch();
    audit_log($pdo, 'sliding_quotes', $_POST['sid'], 'update', $oldData, $newData);
    header('Location: offer_form?id=' . $id);
    exit;
}

// Handle deleting sliding quotes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sliding']) && $id) {
    $stmtOld = $pdo->prepare('SELECT * FROM sliding_quotes WHERE id=:sid');
    $stmtOld->execute([':sid' => $_POST['sid']]);
    $oldData = $stmtOld->fetch();

    $stmt = $pdo->prepare("DELETE FROM sliding_quotes WHERE id=:sid AND master_quote_id=:master");
    $stmt->execute([
        ':sid' => $_POST['sid'],
        ':master' => $id
    ]);
    audit_log($pdo, 'sliding_quotes', $_POST['sid'], 'delete', $oldData, null);
    header('Location: offer_form?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canAdd) {
    $data = [
        ':company'   => $_POST['company_id'],
        ':contact'   => $_POST['contact_id'],
        ':date'      => $_POST['quote_date'],
        ':delivery'  => $_POST['delivery_term'],
        ':method'    => $_POST['payment_method'],
        ':due'       => $_POST['payment_due'],
        ':validity'  => $_POST['quote_validity'],
        ':maturity'  => $_POST['maturity']
    ];

    if ($id) {
        $stmtOld = $pdo->prepare('SELECT * FROM master_quotes WHERE id=:id');
        $stmtOld->execute([':id' => $id]);
        $oldData = $stmtOld->fetch();

        $data[':id'] = $id;
        $stmt = $pdo->prepare(
            "UPDATE master_quotes SET company_id=:company, contact_id=:contact, quote_date=:date, delivery_term=:delivery, payment_method=:method, payment_due=:due, quote_validity=:validity, maturity=:maturity WHERE id=:id"
        );
        $stmt->execute($data);
        $stmtNew = $pdo->prepare('SELECT * FROM master_quotes WHERE id=:id');
        $stmtNew->execute([':id' => $id]);
        $newData = $stmtNew->fetch();
        audit_log($pdo, 'master_quotes', $id, 'update', $oldData, $newData);
    } else {
        $data[':prepared'] = $_SESSION['user']['id'] ?? null;
        $stmt = $pdo->prepare(
            "INSERT INTO master_quotes (company_id, contact_id, quote_date, prepared_by, delivery_term, payment_method, payment_due, quote_validity, maturity) VALUES (:company, :contact, :date, :prepared, :delivery, :method, :due, :validity, :maturity)"
        );
        $stmt->execute($data);
        $newId = $pdo->lastInsertId();
        $stmtNew = $pdo->prepare('SELECT * FROM master_quotes WHERE id=:id');
        $stmtNew->execute([':id' => $newId]);
        $newData = $stmtNew->fetch();
        audit_log($pdo, 'master_quotes', $newId, 'create', null, $newData);
    }
    header('Location: offer');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teklif Formu</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Teklif <?php echo $id ? 'Düzenle' : 'Ekle'; ?></h2>
    <?php if (!$canAdd): ?>
        <div class="alert alert-warning">
            Teklif ekleyebilmek için önce <a href="company" class="alert-link">firma</a> ve <a href="customers"
                class="alert-link">müşteri</a> eklemelisiniz.
        </div>
    <?php else: ?>
        <form method="post">
            <fieldset class="border p-3 mb-4">
                <legend class="w-auto px-2">Firma ve Müşteri</legend>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Firma</label>
                        <select name="company_id" class="form-select" required>
                            <?php foreach ($companies as $co): ?>
                                <option value="<?php echo $co['id']; ?>" <?php echo ($quote && $quote['company_id'] == $co['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($co['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Müşteri</label>
                        <select name="contact_id" class="form-select" required>
                            <?php foreach ($customers as $cu): ?>
                                <option value="<?php echo $cu['id']; ?>" <?php echo ($quote && $quote['contact_id'] == $cu['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cu['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>
            <fieldset class="border p-3 mb-4">
                <legend class="w-auto px-2">Teslimat Bilgileri</legend>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" name="quote_date" class="form-control"
                            value="<?php echo $quote ? htmlspecialchars($quote['quote_date']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teslimat Süresi</label>
                        <input type="text" name="delivery_term" class="form-control"
                            value="<?php echo $quote ? htmlspecialchars($quote['delivery_term']) : ''; ?>">
                    </div>
                </div>
            </fieldset>
            <fieldset class="border p-3 mb-4">
                <legend class="w-auto px-2">Ödeme Bilgileri</legend>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ödeme Yöntemi</label>
                        <select name="payment_method" class="form-select">
                            <?php
                            $methods = ['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet'];
                            $current = $quote['payment_method'] ?? '';
                            foreach ($methods as $method): ?>
                                <option value="<?php echo $method; ?>" <?php echo $current === $method ? 'selected' : ''; ?>>
                                    <?php echo $method; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ödeme Süresi</label>
                        <input type="text" name="payment_due" class="form-control"
                            value="<?php echo $quote ? htmlspecialchars($quote['payment_due']) : ''; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teklif Süresi</label>
                        <input type="text" name="quote_validity" class="form-control"
                            value="<?php echo $quote ? htmlspecialchars($quote['quote_validity']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vade</label>
                        <input type="text" name="maturity" class="form-control"
                            value="<?php echo $quote ? htmlspecialchars($quote['maturity']) : ''; ?>">
                    </div>
                </div>
            </fieldset>
            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
        </form>
        <!-- Giyotin Modal -->
        <div class="modal fade" id="giyotinModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="post" action="offer_form?id=<?php echo $id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Giyotin Teklifi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_guillotine" value="1" id="giyotinAction">
                        <input type="hidden" name="gid" id="giyotinId" value="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genişlik (mm)</label>
                                <input type="number" step="0.01" name="width_mm" id="giyotinWidth" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yükseklik (mm)</label>
                                <input type="number" step="0.01" name="height_mm" id="giyotinHeight" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adet</label>
                                <input type="number" name="system_qty" id="giyotinQty" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Motor Sistemi</label>
                                <select name="motor_system" id="giyotinMotor" class="form-select">
                                    <option value="Somfy">Somfy</option>
                                    <option value="ASA">ASA</option>
                                    <option value="Cuppon">Cuppon</option>
                                    <option value="Mosel">Mosel</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cam</label>
                                <select name="glass_type" id="giyotinGlass" class="form-select">
                                    <option value="Isıcam">Isıcam</option>
                                    <option value="Tek Cam">Tek Cam</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cam Rengi</label>
                                <select name="glass_color" id="giyotinColor" class="form-select">
                                    <option value="Şeffaf">Şeffaf</option>
                                    <option value="Füme">Füme</option>
                                    <option value="Mavi">Mavi</option>
                                    <option value="Yeşil">Yeşil</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kumanda Adedi</label>
                                <input type="number" name="remote_qty" id="giyotinRemoteQty" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RAL Kod</label>
                                <input type="text" name="ral_code" id="giyotinRal" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="button" class="btn btn-info" onclick="openOptimization()">Hesapla</button>
                        <button type="submit" id="giyotinSubmit" class="btn btn-<?php echo get_color(); ?>">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mb-3 d-flex">
            <div class="dropdown ms-auto">
                <button class="btn btn-<?php echo get_color(); ?> dropdown-toggle" type="button" id="offerDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    Teklif Ekle
                </button>
                <ul class="dropdown-menu" aria-labelledby="offerDropdown">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#giyotinModal">Giyotin</a>
                    </li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#surmeModal">Sürme</a></li>
                </ul>
            </div>
        </div>
        <!-- Sürme Modal -->
        <div class="modal fade" id="surmeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="post" action="offer_form?id=<?php echo $id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Sürme Teklifi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_sliding" value="1" id="surmeAction">
                        <input type="hidden" name="sid" id="surmeId" value="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sürme Sistemi</label>
                                <select name="system_type" id="surmeSystem" class="form-select">
                                    <option value="Sistem 1">Sistem 1</option>
                                    <option value="Sistem 2">Sistem 2</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kanat Tipi</label>
                                <input type="text" name="wing_type" id="surmeWing" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genişlik (mm)</label>
                                <input type="number" step="0.01" name="width_mm" id="surmeWidth" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yükseklik (mm)</label>
                                <input type="number" step="0.01" name="height_mm" id="surmeHeight" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistem Adedi</label>
                                <input type="number" name="system_qty" id="surmeQty" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RAL Kod</label>
                                <input type="text" name="ral_code" id="surmeRal" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kenet</label>
                                <select name="fastening_type" id="surmeFastening" class="form-select">
                                    <option value="Takviyesiz">Takviyesiz</option>
                                    <option value="Takviyeli Yarım">Takviyeli Yarım</option>
                                    <option value="Takviyeli Tam">Takviyeli Tam</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cam</label>
                                <select name="glass_type" id="surmeGlass" class="form-select">
                                    <option value="Isıcam">Isıcam</option>
                                    <option value="Tek Cam">Tek Cam</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cam Rengi</label>
                                <select name="glass_color" id="surmeColor" class="form-select">
                                    <option value="Şeffaf">Şeffaf</option>
                                    <option value="Füme">Füme</option>
                                    <option value="Mavi">Mavi</option>
                                    <option value="Yeşil">Yeşil</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kilit</label>
                                <input type="text" name="locking" id="surmeLocking" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" id="surmeSubmit" class="btn btn-<?php echo get_color(); ?>">Ekle</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($id): ?>
            <?php if ($guillotines): ?>
                <h4 class="mt-4">Giyotin Teklifleri</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sistem Tipi</th>
                            <th>En (mm)</th>
                            <th>Boy (mm)</th>
                            <th>Adet</th>
                            <th>Cam</th>
                            <th>Cam Rengi</th>
                            <th>Motor</th>
                            <th>Uzaktan</th>
                            <th>Adet</th>
                            <th>RAL Kod</th>
                            <th class="text-center" style="width:150px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guillotines as $g): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($g['system_type']); ?></td>
                                <td><?php echo htmlspecialchars($g['width_mm']); ?></td>
                                <td><?php echo htmlspecialchars($g['height_mm']); ?></td>
                                <td><?php echo htmlspecialchars($g['system_qty']); ?></td>
                                <td><?php echo htmlspecialchars($g['glass_type']); ?></td>
                                <td><?php echo htmlspecialchars($g['glass_color']); ?></td>
                                <td><?php echo htmlspecialchars($g['motor_system']); ?></td>
                                <td><?php echo htmlspecialchars($g['remote_system']); ?></td>
                                <td><?php echo htmlspecialchars($g['remote_qty']); ?></td>
                                <td><?php echo htmlspecialchars($g['ral_code']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm bg-light text-dark edit-guillotine-btn" data-bs-toggle="modal"
                                        data-bs-target="#giyotinModal" data-id="<?php echo $g['id']; ?>"
                                        data-width="<?php echo htmlspecialchars($g['width_mm']); ?>"
                                        data-height="<?php echo htmlspecialchars($g['height_mm']); ?>"
                                        data-qty="<?php echo htmlspecialchars($g['system_qty']); ?>"
                                        data-glass="<?php echo htmlspecialchars($g['glass_type']); ?>"
                                        data-color="<?php echo htmlspecialchars($g['glass_color']); ?>"
                                        data-motor="<?php echo htmlspecialchars($g['motor_system']); ?>"
                                        data-remote="<?php echo htmlspecialchars($g['remote_qty']); ?>"
                                        data-ral="<?php echo htmlspecialchars($g['ral_code']); ?>">
                                        Düzenle
                                    </button>
                                    <form method="post" action="offer_form?id=<?php echo $id; ?>" style="display:inline-block"
                                        onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="delete_guillotine" value="1">
                                        <input type="hidden" name="gid" value="<?php echo $g['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($slidings): ?>
                <h4 class="mt-4">Sürme Teklifleri</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sistem Tipi</th>
                            <th>En (mm)</th>
                            <th>Boy (mm)</th>
                            <th>Kanat Tipi</th>
                            <th>Kenet</th>
                            <th>Cam</th>
                            <th>Cam Rengi</th>
                            <th>Adet</th>
                            <th>RAL Kod</th>
                            <th>Kilit</th>
                            <th class="text-center" style="width:150px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slidings as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['system_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['width_mm']); ?></td>
                                <td><?php echo htmlspecialchars($s['height_mm']); ?></td>
                                <td><?php echo htmlspecialchars($s['wing_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['fastening_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['glass_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['glass_color']); ?></td>
                                <td><?php echo htmlspecialchars($s['system_qty']); ?></td>
                                <td><?php echo htmlspecialchars($s['ral_code']); ?></td>
                                <td><?php echo htmlspecialchars($s['locking']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm bg-light text-dark edit-sliding-btn" data-bs-toggle="modal"
                                        data-bs-target="#surmeModal" data-id="<?php echo $s['id']; ?>"
                                        data-system="<?php echo htmlspecialchars($s['system_type']); ?>"
                                        data-wing="<?php echo htmlspecialchars($s['wing_type']); ?>"
                                        data-width="<?php echo htmlspecialchars($s['width_mm']); ?>"
                                        data-height="<?php echo htmlspecialchars($s['height_mm']); ?>"
                                        data-qty="<?php echo htmlspecialchars($s['system_qty']); ?>"
                                        data-ral="<?php echo htmlspecialchars($s['ral_code']); ?>"
                                        data-fastening="<?php echo htmlspecialchars($s['fastening_type']); ?>"
                                        data-glass="<?php echo htmlspecialchars($s['glass_type']); ?>"
                                        data-color="<?php echo htmlspecialchars($s['glass_color']); ?>"
                                        data-locking="<?php echo htmlspecialchars($s['locking']); ?>">
                                        Düzenle
                                    </button>
                                    <form method="post" action="offer_form?id=<?php echo $id; ?>" style="display:inline-block"
                                        onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="delete_sliding" value="1">
                                        <input type="hidden" name="sid" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <script>
            function openOptimization() {
                const width = document.getElementById('giyotinWidth').value;
                const height = document.getElementById('giyotinHeight').value;
                const quantity = document.getElementById('giyotinQty').value || 1;
                const glass = document.getElementById('giyotinGlass').value;
                const gid = document.getElementById('giyotinId').value;

                if (!width || !height) {
                    alert('Lütfen genişlik ve yükseklik giriniz.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/optimization.php';
                form.target = '_blank';
                form.innerHTML =
                    '<input type="hidden" name="width" value="' + width + '">' +
                    '<input type="hidden" name="height" value="' + height + '">' +
                    '<input type="hidden" name="quantity" value="' + quantity + '">' +
                    '<input type="hidden" name="glass_type" value="' + glass + '">' +
                    '<input type="hidden" name="gid" value="' + gid + '">';
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }

            document.addEventListener("DOMContentLoaded", function () {
                const rowsContainer = document.getElementById("quoteRows");
                const addRowBtn = document.getElementById("addRow");

                if (addRowBtn) {
                    addRowBtn.addEventListener("click", function () {
                        const firstRow = rowsContainer.querySelector(".form-row");
                        const newRow = firstRow.cloneNode(true);
                        newRow.querySelectorAll("input").forEach(input => input.value = "");
                        rowsContainer.appendChild(newRow);
                    });
                }

                rowsContainer && rowsContainer.addEventListener("click", function (e) {
                    if (e.target.classList.contains("delete-row")) {
                        const rows = rowsContainer.querySelectorAll(".form-row");
                        if (rows.length === 1) {
                            alert("En az bir satır kalmalıdır.");
                            return;
                        }
                        e.target.closest(".form-row").remove();
                    }
                });

                const giyotinModal = document.getElementById("giyotinModal");
                const actionInput = document.getElementById("giyotinAction");
                const idInput = document.getElementById("giyotinId");
                const widthInput = document.getElementById("giyotinWidth");
                const heightInput = document.getElementById("giyotinHeight");
                const qtyInput = document.getElementById("giyotinQty");
                const motorInput = document.getElementById("giyotinMotor");
                const glassInput = document.getElementById("giyotinGlass");
                const colorInput = document.getElementById("giyotinColor");
                const remoteInput = document.getElementById("giyotinRemoteQty");
                const ralInput = document.getElementById("giyotinRal");
                const submitBtn = document.getElementById("giyotinSubmit");

                document.querySelectorAll('.edit-guillotine-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        actionInput.name = 'edit_guillotine';
                        idInput.value = this.dataset.id;
                        widthInput.value = this.dataset.width;
                        heightInput.value = this.dataset.height;
                        qtyInput.value = this.dataset.qty;
                        motorInput.value = this.dataset.motor;
                        glassInput.value = this.dataset.glass;
                        colorInput.value = this.dataset.color;
                        remoteInput.value = this.dataset.remote;
                        ralInput.value = this.dataset.ral;
                        submitBtn.textContent = 'Kaydet';
                        giyotinModal.querySelector('.modal-title').textContent = 'Giyotin Düzenle';
                    });
                });

                giyotinModal.addEventListener('hidden.bs.modal', function () {
                    actionInput.name = 'add_guillotine';
                    idInput.value = '';
                    widthInput.value = '';
                    heightInput.value = '';
                    qtyInput.value = '';
                    motorInput.value = 'Somfy';
                    glassInput.value = 'Isıcam';
                    colorInput.value = 'Şeffaf';
                    remoteInput.value = '';
                    ralInput.value = '';
                    submitBtn.textContent = 'Ekle';
                    giyotinModal.querySelector('.modal-title').textContent = 'Giyotin Teklifi';
                });

                const surmeModal = document.getElementById('surmeModal');
                const surmeAction = document.getElementById('surmeAction');
                const surmeId = document.getElementById('surmeId');
                const surmeSystem = document.getElementById('surmeSystem');
                const surmeWing = document.getElementById('surmeWing');
                const surmeWidth = document.getElementById('surmeWidth');
                const surmeHeight = document.getElementById('surmeHeight');
                const surmeQty = document.getElementById('surmeQty');
                const surmeRal = document.getElementById('surmeRal');
                const surmeFastening = document.getElementById('surmeFastening');
                const surmeGlass = document.getElementById('surmeGlass');
                const surmeColor = document.getElementById('surmeColor');
                const surmeLocking = document.getElementById('surmeLocking');
                const surmeSubmit = document.getElementById('surmeSubmit');

                document.querySelectorAll('.edit-sliding-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        surmeAction.name = 'edit_sliding';
                        surmeId.value = this.dataset.id;
                        surmeSystem.value = this.dataset.system;
                        surmeWing.value = this.dataset.wing;
                        surmeWidth.value = this.dataset.width;
                        surmeHeight.value = this.dataset.height;
                        surmeQty.value = this.dataset.qty;
                        surmeRal.value = this.dataset.ral;
                        surmeFastening.value = this.dataset.fastening;
                        surmeGlass.value = this.dataset.glass;
                        surmeColor.value = this.dataset.color;
                        surmeLocking.value = this.dataset.locking;
                        surmeSubmit.textContent = 'Kaydet';
                        surmeModal.querySelector('.modal-title').textContent = 'Sürme Düzenle';
                    });
                });

                surmeModal.addEventListener('hidden.bs.modal', function () {
                    surmeAction.name = 'add_sliding';
                    surmeId.value = '';
                    surmeSystem.value = 'Sistem 1';
                    surmeWing.value = '';
                    surmeWidth.value = '';
                    surmeHeight.value = '';
                    surmeQty.value = '';
                    surmeRal.value = '';
                    surmeFastening.value = 'Takviyesiz';
                    surmeGlass.value = 'Isıcam';
                    surmeColor.value = 'Şeffaf';
                    surmeLocking.value = '';
                    surmeSubmit.textContent = 'Ekle';
                    surmeModal.querySelector('.modal-title').textContent = 'Sürme Teklifi';
                });
            });
        </script>
    <?php endif; ?>
</div>
</body>
</html>
