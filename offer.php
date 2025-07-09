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

// CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO master_quotes (company_id, contact_id, quote_date, prepared_by, delivery_term, payment_method, payment_due, quote_validity, maturity) VALUES (:company, :contact, :date, :prepared, :delivery, :method, :due, :validity, :maturity)");
        $stmt->execute([
            ':company'   => $_POST['company_id'],
            ':contact'   => $_POST['contact_id'],
            ':date'      => $_POST['quote_date'],
            ':prepared'  => $_SESSION['user']['id'] ?? null,
            ':delivery'  => $_POST['delivery_term'],
            ':method'    => $_POST['payment_method'],
            ':due'       => $_POST['payment_due'],
            ':validity'  => $_POST['quote_validity'],
            ':maturity'  => $_POST['maturity']
        ]);
        header('Location: offer');
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE master_quotes SET company_id=:company, contact_id=:contact, quote_date=:date, delivery_term=:delivery, payment_method=:method, payment_due=:due, quote_validity=:validity, maturity=:maturity WHERE id=:id");
        $stmt->execute([
            ':company'  => $_POST['company_id'],
            ':contact'  => $_POST['contact_id'],
            ':date'     => $_POST['quote_date'],
            ':delivery' => $_POST['delivery_term'],
            ':method'   => $_POST['payment_method'],
            ':due'      => $_POST['payment_due'],
            ':validity' => $_POST['quote_validity'],
            ':maturity' => $_POST['maturity'],
            ':id'       => $_POST['id']
        ]);
        header('Location: offer');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM master_quotes WHERE id=:id");
        $stmt->execute([':id' => $_POST['id']]);
        header('Location: offer');
        exit;
    }
}

// Fetch quotes
$query = "SELECT mq.*, co.name AS company_name, CONCAT(cu.first_name,' ',cu.last_name) AS customer_name FROM master_quotes mq LEFT JOIN companies co ON mq.company_id = co.id LEFT JOIN customers cu ON mq.contact_id = cu.id ORDER BY mq.quote_date DESC";
$stmt = $pdo->query($query);
$quotes = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="container py-4">
    <h2 class="mb-4">Teklifler</h2>
    <?php if (!$canAdd): ?>
        <div class="alert alert-warning">
            Teklif ekleyebilmek için önce <a href="company" class="alert-link">firma</a> ve <a href="customers" class="alert-link">müşteri</a> eklemelisiniz.
        </div>
    <?php endif; ?>
    <div class="row mb-3">
        <div class="col-12 text-end">
            <?php if ($canAdd): ?>
                <button type="button" class="btn btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#addModal">Teklif Ekle</button>
            <?php endif; ?>
        </div>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Firma</th>
                <th>Müşteri</th>
                <th>Tarih</th>
                <th>Teslimat Süresi</th>
                <th>Ödeme Yöntemi</th>
                <th>Ödeme Süresi</th>
                <th>Teklif Süresi</th>
                <th>Vade</th>
                <th class="text-center" style="width:150px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quotes as $q): ?>
                <tr>
                    <td><?php echo htmlspecialchars($q['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($q['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($q['quote_date']); ?></td>
                    <td><?php echo htmlspecialchars($q['delivery_term']); ?></td>
                    <td><?php echo htmlspecialchars($q['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($q['payment_due']); ?></td>
                    <td><?php echo htmlspecialchars($q['quote_validity']); ?></td>
                    <td><?php echo htmlspecialchars($q['maturity']); ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $q['id']; ?>">Düzenle</button>
                        <form method="post" action="offer" style="display:inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $q['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Teklif Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" action="offer">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Firma</label>
                                            <select name="company_id" class="form-select" required>
                                                <?php foreach ($companies as $co): ?>
                                                    <option value="<?php echo $co['id']; ?>" <?php echo ($q['company_id'] == $co['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($co['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Müşteri</label>
                                            <select name="contact_id" class="form-select" required>
                                                <?php foreach ($customers as $cu): ?>
                                                    <option value="<?php echo $cu['id']; ?>" <?php echo ($q['contact_id'] == $cu['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cu['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tarih</label>
                                            <input type="date" name="quote_date" class="form-control" value="<?php echo htmlspecialchars($q['quote_date']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Teslimat Süresi</label>
                                            <input type="text" name="delivery_term" class="form-control" value="<?php echo htmlspecialchars($q['delivery_term']); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ödeme Yöntemi</label>
                                            <input type="text" name="payment_method" class="form-control" value="<?php echo htmlspecialchars($q['payment_method']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ödeme Süresi</label>
                                            <input type="text" name="payment_due" class="form-control" value="<?php echo htmlspecialchars($q['payment_due']); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Teklif Süresi</label>
                                            <input type="text" name="quote_validity" class="form-control" value="<?php echo htmlspecialchars($q['quote_validity']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Vade</label>
                                            <input type="text" name="maturity" class="form-control" value="<?php echo htmlspecialchars($q['maturity']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                    <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teklif Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="offer">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firma</label>
                            <select name="company_id" class="form-select" required>
                                <?php foreach ($companies as $co): ?>
                                    <option value="<?php echo $co['id']; ?>"><?php echo htmlspecialchars($co['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Müşteri</label>
                            <select name="contact_id" class="form-select" required>
                                <?php foreach ($customers as $cu): ?>
                                    <option value="<?php echo $cu['id']; ?>"><?php echo htmlspecialchars($cu['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tarih</label>
                            <input type="date" name="quote_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teslimat Süresi</label>
                            <input type="text" name="delivery_term" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <input type="text" name="payment_method" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ödeme Süresi</label>
                            <input type="text" name="payment_due" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teklif Süresi</label>
                            <input type="text" name="quote_validity" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vade</label>
                            <input type="text" name="maturity" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-<?php echo get_color(); ?>">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
