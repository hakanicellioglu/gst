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
$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';
$query = "SELECT mq.*, co.name AS company_name, CONCAT(cu.first_name,' ',cu.last_name) AS customer_name
          FROM master_quotes mq
          LEFT JOIN companies co ON mq.company_id = co.id
          LEFT JOIN customers cu ON mq.contact_id = cu.id
          WHERE co.name LIKE :search1 OR CONCAT(cu.first_name,' ',cu.last_name) LIKE :search2
          ORDER BY mq.quote_date $sort";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':search1' => "%$search%",
    ':search2' => "%$search%"
]);
$quotes = $stmt->fetchAll();
$view = $_GET['view'] ?? 'list';

$p = $_GET;
$p['view'] = 'list';
$listUrl = 'offer?' . http_build_query($p);
$p['view'] = 'card';
$cardUrl = 'offer?' . http_build_query($p);

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
            <button type="button" class="btn btn-dark me-2" data-bs-toggle="modal" data-bs-target="#filterModal">Filtrele</button>
            <?php if ($canAdd): ?>
                <a href="offer_form" class="btn btn-<?php echo get_color(); ?>">Teklif Ekle</a>
            <?php endif; ?>
            <div class="btn-group ms-2" role="group">
                <a href="<?php echo $listUrl; ?>" class="btn btn-outline-secondary <?php echo $view === 'list' ? 'active' : ''; ?>"><i class="bi bi-list"></i></a>
                <a href="<?php echo $cardUrl; ?>" class="btn btn-outline-secondary <?php echo $view === 'card' ? 'active' : ''; ?>"><i class="bi bi-grid"></i></a>
            </div>
        </div>
    </div>

    <?php if ($view === 'list'): ?>
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
                        <a href="offer_form?id=<?php echo $q['id']; ?>" class="btn btn-sm btn-<?php echo get_color(); ?>">Düzenle</a>
                        <form method="post" action="offer" style="display:inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="row">
        <?php foreach ($quotes as $q): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($q['company_name']); ?></h5>
                        <p class="card-text">
                            Müşteri: <?php echo htmlspecialchars($q['customer_name']); ?><br>
                            Tarih: <?php echo htmlspecialchars($q['quote_date']); ?><br>
                            Teslimat: <?php echo htmlspecialchars($q['delivery_term']); ?><br>
                            Ödeme Yöntemi: <?php echo htmlspecialchars($q['payment_method']); ?><br>
                            Ödeme Süresi: <?php echo htmlspecialchars($q['payment_due']); ?><br>
                            Teklif Süresi: <?php echo htmlspecialchars($q['quote_validity']); ?><br>
                            Vade: <?php echo htmlspecialchars($q['maturity']); ?>
                        </p>
                        <div class="text-end">
                            <a href="offer_form?id=<?php echo $q['id']; ?>" class="btn btn-sm btn-<?php echo get_color(); ?>">Düzenle</a>
                            <form method="post" action="offer" style="display:inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="get" action="offer">
            <div class="modal-header">
                <h5 class="modal-title">Filtrele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ara</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Teklif ara">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sıralama</label>
                    <select name="sort" class="form-select">
                        <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>A'dan Z'ye</option>
                        <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Z'den A'ya</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="submit" class="btn btn-<?php echo get_color(); ?>">Filtrele</button>
            </div>
        </form>
    </div>
</div>

