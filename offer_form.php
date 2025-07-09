<?php
require_once 'config.php';
require_once 'helpers/theme.php';
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
        ':master'     => $id,
        ':width'      => $_POST['width_mm'],
        ':height'     => $_POST['height_mm'],
        ':qty'        => $_POST['system_qty'],
        ':glass'      => $_POST['glass_type'],
        ':color'      => $_POST['glass_color'],
        ':motor'      => $_POST['motor_system'],
        ':remote_qty' => $_POST['remote_qty'],
        ':ral'        => $_POST['ral_code']
    ]);
    header('Location: offer_form?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canAdd) {
    $data = [
        ':company' => $_POST['company_id'],
        ':contact' => $_POST['contact_id'],
        ':date' => $_POST['quote_date'],
        ':prepared' => $_SESSION['user']['id'] ?? null,
        ':delivery' => $_POST['delivery_term'],
        ':method' => $_POST['payment_method'],
        ':due' => $_POST['payment_due'],
        ':validity' => $_POST['quote_validity'],
        ':maturity' => $_POST['maturity']
    ];
    if ($id) {
        $data[':id'] = $id;
        $stmt = $pdo->prepare("UPDATE master_quotes SET company_id=:company, contact_id=:contact, quote_date=:date, delivery_term=:delivery, payment_method=:method, payment_due=:due, quote_validity=:validity, maturity=:maturity WHERE id=:id");
    } else {
        $stmt = $pdo->prepare("INSERT INTO master_quotes (company_id, contact_id, quote_date, prepared_by, delivery_term, payment_method, payment_due, quote_validity, maturity) VALUES (:company, :contact, :date, :prepared, :delivery, :method, :due, :validity, :maturity)");
    }
    $stmt->execute($data);
    header('Location: offer');
    exit;
}

include 'includes/header.php';
?>
<div class="container py-4">
    <h2 class="mb-4">Teklif <?php echo $id ? 'Düzenle' : 'Ekle'; ?></h2>
    <?php if (!$canAdd): ?>
        <div class="alert alert-warning">
            Teklif ekleyebilmek için önce <a href="company" class="alert-link">firma</a> ve <a href="customers"
                class="alert-link">müşteri</a> eklemelisiniz.
        </div>
    <?php else: ?>
        <form method="post">
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
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ödeme Yöntemi</label>
                    <input type="text" name="payment_method" class="form-control"
                        value="<?php echo $quote ? htmlspecialchars($quote['payment_method']) : ''; ?>">
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
                        <input type="hidden" name="add_guillotine" value="1">
                        <div class="mb-3">
                            <label class="form-label">Genişlik (mm)</label>
                            <input type="number" step="0.01" name="width_mm" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yükseklik (mm)</label>
                            <input type="number" step="0.01" name="height_mm" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adet</label>
                            <input type="number" name="system_qty" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cam</label>
                            <select name="glass_type" class="form-select">
                                <option value="Isıcam">Isıcam</option>
                                <option value="Tek Cam">Tek Cam</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cam Rengi</label>
                            <input type="text" name="glass_color" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motor Sistemi</label>
                            <input type="text" name="motor_system" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kumanda Adedi</label>
                            <input type="number" name="remote_qty" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RAL Kod</label>
                            <input type="text" name="ral_code" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-<?php echo get_color(); ?>">Ekle</button>
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
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sürme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Sürme seçildi.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    </div>
                </div>
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
                            <th>Montaj</th>
                            <th>Adet</th>
                            <th>RAL Kod</th>
                            <th>Kilit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slidings as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['system_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['width_mm']); ?></td>
                                <td><?php echo htmlspecialchars($s['height_mm']); ?></td>
                                <td><?php echo htmlspecialchars($s['fastening_type']); ?></td>
                                <td><?php echo htmlspecialchars($s['system_qty']); ?></td>
                                <td><?php echo htmlspecialchars($s['ral_code']); ?></td>
                                <td><?php echo htmlspecialchars($s['locking']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const rowsContainer = document.getElementById("quoteRows");
                const addRowBtn = document.getElementById("addRow");

                addRowBtn.addEventListener("click", function () {
                    const firstRow = rowsContainer.querySelector(".form-row");
                    const newRow = firstRow.cloneNode(true);
                    newRow.querySelectorAll("input").forEach(input => input.value = "");
                    rowsContainer.appendChild(newRow);
                });

                rowsContainer.addEventListener("click", function (e) {
                    if (e.target.classList.contains("delete-row")) {
                        const rows = rowsContainer.querySelectorAll(".form-row");
                        if (rows.length === 1) {
                            alert("En az bir satır kalmalıdır.");
                            return;
                        }
                        e.target.closest(".form-row").remove();
                    }
                });
            });
        </script>
    <?php endif; ?>
</div>
