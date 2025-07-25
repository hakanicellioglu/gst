<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/device.php';
// Log create, update and delete actions
require_once 'helpers/audit.php';
require_once 'helpers/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}
load_theme_settings($pdo);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teklifler</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <?php

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
        $stmt = $pdo->prepare("INSERT INTO master_quotes (company_id, contact_id, quote_date, prepared_by, delivery_term, payment_method, payment_due, quote_validity, maturity, assembly_type) VALUES (:company, :contact, :date, :prepared, :delivery, :method, :due, :validity, :maturity, :assembly)");
        $stmt->execute([
            ':company'   => $_POST['company_id'],
            ':contact'   => $_POST['contact_id'],
            ':date'      => $_POST['quote_date'],
            ':prepared'  => $_SESSION['user']['id'] ?? null,
            ':delivery'  => $_POST['delivery_term'],
            ':method'    => $_POST['payment_method'],
            ':due'       => $_POST['payment_due'],
            ':validity'  => $_POST['quote_validity'],
            ':maturity'  => $_POST['maturity'],
            ':assembly'  => $_POST['assembly_type']
        ]);
        $newId = $pdo->lastInsertId();
        $stmtData = $pdo->prepare('SELECT * FROM master_quotes WHERE id = :id');
        $stmtData->execute([':id' => $newId]);
        $newData = $stmtData->fetch();
        logAction($pdo, 'master_quotes', $newId, 'create', null, $newData);
        header('Location: offer');
        exit;
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $stmtOld = $pdo->prepare('SELECT * FROM master_quotes WHERE id = :id');
        $stmtOld->execute([':id' => $id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("UPDATE master_quotes SET company_id=:company, contact_id=:contact, quote_date=:date, delivery_term=:delivery, payment_method=:method, payment_due=:due, quote_validity=:validity, maturity=:maturity, assembly_type=:assembly WHERE id=:id");
        $stmt->execute([
            ':company'  => $_POST['company_id'],
            ':contact'  => $_POST['contact_id'],
            ':date'     => $_POST['quote_date'],
            ':delivery' => $_POST['delivery_term'],
            ':method'   => $_POST['payment_method'],
            ':due'      => $_POST['payment_due'],
            ':validity' => $_POST['quote_validity'],
            ':maturity' => $_POST['maturity'],
            ':assembly' => $_POST['assembly_type'],
            ':id'       => $id
        ]);
        $stmtNew = $pdo->prepare('SELECT * FROM master_quotes WHERE id = :id');
        $stmtNew->execute([':id' => $id]);
        $newData = $stmtNew->fetch();
        logAction($pdo, 'master_quotes', $id, 'update', $oldData, $newData);
        header('Location: offer');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmtOld = $pdo->prepare('SELECT * FROM master_quotes WHERE id = :id');
        $stmtOld->execute([':id' => $id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("DELETE FROM master_quotes WHERE id=:id");
        $stmt->execute([':id' => $id]);
        logAction($pdo, 'master_quotes', $id, 'delete', $oldData, null);
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
$view = resolve_view();

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
            Teklif ekleyebilmek için önce <a href="company" class="alert-link">firma</a> ve <a href="customers"
                class="alert-link">müşteri</a> eklemelisiniz.
        </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="row align-items-center justify-content-end">
                    <div class="col-auto">
                        <form method="get" class="form-inline-responsive d-flex">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                class="form-control" placeholder="Teklif ara">
                            <input type="hidden" name="sort" value="<?php echo strtolower($sort); ?>">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                            <button type="submit" class="btn btn-<?php echo get_color(); ?> ms-2">Ara</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <form method="get" class="form-inline-responsive d-flex ms-2">
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>A'dan Z'ye</option>
                                <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Z'den A'ya</option>
                            </select>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        </form>
                    </div>
                    <?php if ($canAdd): ?>
                        <div class="col-auto">
                            <a href="offer_form" class="btn btn-<?php echo get_color(); ?> ms-2">Teklif Ekle</a>
                        </div>
                    <?php endif; ?>
                    <div class="col-auto">
                        <div class="btn-group ms-2 view-toggle d-none d-md-inline-flex" role="group">
                            <a href="<?php echo $listUrl; ?>"
                                class="btn btn-outline-secondary <?php echo $view === 'list' ? 'active' : ''; ?>"><i
                                    class="bi bi-list"></i></a>
                            <a href="<?php echo $cardUrl; ?>"
                                class="btn btn-outline-secondary <?php echo $view === 'card' ? 'active' : ''; ?>"><i
                                    class="bi bi-grid"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($view === 'list'): ?>
        <table class="table table-bordered table-striped responsive-table">
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
                    <th>Montaj</th>
                    <th class="text-center actions-col">İşlemler</th>
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
                    <td><?php echo htmlspecialchars($q['assembly_type']); ?></td>
                    <td class="text-center actions-col">
                        <a href="offer_form?id=<?php echo $q['id']; ?>" class="btn btn-sm bg-light text-dark"><i
                                class="bi bi-pencil"></i></a>
                        <?php if (is_admin($pdo)): ?>
                        <a href="log-list.php?table=master_quotes&id=<?php echo $q['id']; ?>"
                            class="btn btn-sm bg-light text-dark" title="Logları Gör"><i class="bi bi-eye"></i></a>
                        <?php endif; ?>
                        <a href="pdf.php?id=<?php echo $q['id']; ?>" target="_blank"
                            class="btn btn-sm bg-light text-dark" title="PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <form method="post" action="offer" class="d-inline-block"
                            onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="row g-3 cards-row">
            <?php foreach ($quotes as $q): ?>
            <div class="col-12 col-md-4">
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
                            Vade: <?php echo htmlspecialchars($q['maturity']); ?><br>
                            Montaj: <?php echo htmlspecialchars($q['assembly_type']); ?>
                        </p>
                        <div class="text-end">
                            <a href="offer_form?id=<?php echo $q['id']; ?>" class="btn btn-sm bg-light text-dark"><i
                                    class="bi bi-pencil"></i></a>
                            <?php if (is_admin($pdo)): ?>
                            <a href="log-list.php?table=master_quotes&id=<?php echo $q['id']; ?>"
                                class="btn btn-sm bg-light text-dark" title="Logları Gör"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                            <a href="pdf.php?id=<?php echo $q['id']; ?>" target="_blank"
                                class="btn btn-sm bg-light text-dark" title="PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <form method="post" action="offer" class="d-inline-block"
                                onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
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
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            class="form-control" placeholder="Teklif ara">
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

</body>

</html>