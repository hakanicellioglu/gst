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


// Load companies for dropdowns
$companyStmt = $pdo->query("SELECT id, name FROM companies ORDER BY name");
$companies = $companyStmt->fetchAll();
$canAddCustomer = count($companies) > 0;

// CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO customers (company_id, first_name, last_name, title, email, phone, address) VALUES (:company, :first, :last, :title, :email, :phone, :address)");
        $stmt->execute([
            ':company' => $_POST['company_id'],
            ':first' => $_POST['first_name'],
            ':last' => $_POST['last_name'],
            ':title' => $_POST['title'],
            ':email' => $_POST['email'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address']
        ]);
        $newId = $pdo->lastInsertId();
        $stmtData = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $stmtData->execute([':id' => $newId]);
        $newData = $stmtData->fetch();
        logAction($pdo, 'customers', $newId, 'create', null, $newData);
        header('Location: customers');
        exit;
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $stmtOld = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $stmtOld->execute([':id' => $id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("UPDATE customers SET company_id = :company, first_name = :first, last_name = :last, title = :title, email = :email, phone = :phone, address = :address WHERE id = :id");
        $stmt->execute([
            ':company' => $_POST['company_id'],
            ':first' => $_POST['first_name'],
            ':last' => $_POST['last_name'],
            ':title' => $_POST['title'],
            ':email' => $_POST['email'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address'],
            ':id' => $id
        ]);
        $stmtNew = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $stmtNew->execute([':id' => $id]);
        $newData = $stmtNew->fetch();
        logAction($pdo, 'customers', $id, 'update', $oldData, $newData);
        header('Location: customers');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmtOld = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $stmtOld->execute([':id' => $id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        logAction($pdo, 'customers', $id, 'delete', $oldData, null);
        header('Location: customers');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';
$view = resolve_view();

$params = $_GET;
$params['view'] = 'list';
$listUrl = 'customers?' . http_build_query($params);
$params['view'] = 'card';
$cardUrl = 'customers?' . http_build_query($params);

$query = "SELECT c.*, co.name AS company_name FROM customers c LEFT JOIN companies co ON c.company_id = co.id WHERE c.first_name LIKE :search1 OR c.last_name LIKE :search2 ORDER BY c.first_name $sort, c.last_name $sort";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':search1' => "%$search%",
    ':search2' => "%$search%"
]);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Müşteriler</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <h2 class="mb-4">Müşteriler</h2>
        <?php if (!$canAddCustomer): ?>
            <div class="alert alert-warning">
                Müşteri ekleyebilmek için önce <a href="company" class="alert-link">firma</a> eklemelisiniz.
            </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="row align-items-center justify-content-end">
                    <div class="col-auto">
                        <form method="get" class="form-inline-responsive d-flex">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                class="form-control" placeholder="Müşteri ara">
                            <input type="hidden" name="sort" value="<?php echo strtolower($sort); ?>">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                            <button type="submit" class="btn btn-<?php echo get_color(); ?> ms-2">Ara</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <form method="get" class="form-inline-responsive d-flex ms-2">
                            <select name="sort" class="form-select">
                                <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>A'dan Z'ye</option>
                                <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Z'den A'ya</option>
                            </select>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        </form>
                    </div>
                    <?php if ($canAddCustomer): ?>
                        <div class="col-auto">
                            <button type="button" class="btn btn-<?php echo get_color(); ?> ms-2" data-bs-toggle="modal"
                                data-bs-target="#addModal">Müşteri
                                Ekle</button>
                        </div>
                    <?php else: ?>
                        <div class="col-auto">
                            <a href="company" class="btn btn-<?php echo get_color(); ?> ms-2">Firma Ekle</a>
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
                        <th>İsim</th>
                        <th>Soyisim</th>
                        <th>Firma</th>
                        <th>Ünvan</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Adres</th>
                        <th class="text-center actions-col">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['title']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td class="text-center actions-col">
                                <button class="btn btn-sm bg-light text-dark" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?php echo $customer['id']; ?>"><i class="bi bi-pencil"></i></button>
                                <?php if (is_admin($pdo)): ?>
                                <a href="log-list.php?table=customers&id=<?php echo $customer['id']; ?>" class="btn btn-sm bg-light text-dark" title="Logları Gör"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                                <form method="post" action="customers" class="d-inline-block"
                                    onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $customer['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Müşteri Düzenle</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form method="post" action="customers">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">İsim</label>
                                                <input type="text" name="first_name"
                                                    value="<?php echo htmlspecialchars($customer['first_name']); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Soyisim</label>
                                                <input type="text" name="last_name"
                                                    value="<?php echo htmlspecialchars($customer['last_name']); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Firma</label>
                                                <select name="company_id" class="form-select" required>
                                                    <?php foreach ($companies as $co): ?>
                                                        <option value="<?php echo $co['id']; ?>" <?php echo ($customer['company_id'] == $co['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($co['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ünvan</label>
                                                <select name="title" class="form-select">
                                                    <option value="Hanım" <?php echo $customer['title'] === 'Hanım' ? 'selected' : ''; ?>>Hanım</option>
                                                    <option value="Bey" <?php echo $customer['title'] === 'Bey' ? 'selected' : ''; ?>>
                                                        Bey</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email"
                                                    value="<?php echo htmlspecialchars($customer['email']); ?>"
                                                    class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Telefon</label>
                                                <input type="text" name="phone"
                                                    value="<?php echo htmlspecialchars($customer['phone']); ?>"
                                                    class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Adres</label>
                                                <input type="text" name="address"
                                                    value="<?php echo htmlspecialchars($customer['address']); ?>"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Kapat</button>
                                            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="row g-3 cards-row">
                <?php foreach ($customers as $customer): ?>
                    <div class="col-12 col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                                <p class="card-text">
                                    Firma: <?php echo htmlspecialchars($customer['company_name']); ?><br>
                                    Ünvan: <?php echo htmlspecialchars($customer['title']); ?><br>
                                    Email: <?php echo htmlspecialchars($customer['email']); ?><br>
                                    Telefon: <?php echo htmlspecialchars($customer['phone']); ?><br>
                                    Adres: <?php echo htmlspecialchars($customer['address']); ?>
                                </p>
                                <div class="text-end">
                                    <button class="btn btn-sm bg-light text-dark" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $customer['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <?php if (is_admin($pdo)): ?>
                                    <a href="log-list.php?table=customers&id=<?php echo $customer['id']; ?>" class="btn btn-sm bg-light text-dark" title="Logları Gör"><i class="bi bi-eye"></i></a>
                                    <?php endif; ?>
                                    <form method="post" action="customers" class="d-inline-block"
                                        onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $customer['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Müşteri Düzenle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="customers">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">İsim</label>
                                            <input type="text" name="first_name"
                                                value="<?php echo htmlspecialchars($customer['first_name']); ?>"
                                                class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Soyisim</label>
                                            <input type="text" name="last_name"
                                                value="<?php echo htmlspecialchars($customer['last_name']); ?>"
                                                class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Firma</label>
                                            <select name="company_id" class="form-select" required>
                                                <?php foreach ($companies as $co): ?>
                                                    <option value="<?php echo $co['id']; ?>" <?php echo ($customer['company_id'] == $co['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($co['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Ünvan</label>
                                            <select name="title" class="form-select">
                                                <option value="Hanım" <?php echo $customer['title'] === 'Hanım' ? 'selected' : ''; ?>>Hanım</option>
                                                <option value="Bey" <?php echo $customer['title'] === 'Bey' ? 'selected' : ''; ?>>
                                                    Bey</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email"
                                                value="<?php echo htmlspecialchars($customer['email']); ?>"
                                                class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Telefon</label>
                                            <input type="text" name="phone"
                                                value="<?php echo htmlspecialchars($customer['phone']); ?>"
                                                class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Adres</label>
                                            <input type="text" name="address"
                                                value="<?php echo htmlspecialchars($customer['address']); ?>"
                                                class="form-control">
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
            </div>
        <?php endif; ?>
    </div>
    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtrele</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="get">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ara</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                class="form-control" placeholder="Müşteri ara">
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
    </div>
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Müşteri Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="customers">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">İsim</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Soyisim</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Firma</label>
                            <select name="company_id" class="form-select" required>
                                <?php foreach ($companies as $co): ?>
                                    <option value="<?php echo $co['id']; ?>">
                                        <?php echo htmlspecialchars($co['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ünvan</label>
                            <select name="title" class="form-select">
                                <option value="Hanım">Hanım</option>
                                <option value="Bey">Bey</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <input type="text" name="address" class="form-control">
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

</body>

</html>