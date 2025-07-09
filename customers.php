<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
load_theme_settings($pdo);
include 'includes/header.php';

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
            ':first'   => $_POST['first_name'],
            ':last'    => $_POST['last_name'],
            ':title'   => $_POST['title'],
            ':email'   => $_POST['email'],
            ':phone'   => $_POST['phone'],
            ':address' => $_POST['address']
        ]);
        header('Location: auth.php');
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE customers SET company_id = :company, first_name = :first, last_name = :last, title = :title, email = :email, phone = :phone, address = :address WHERE id = :id");
        $stmt->execute([
            ':company' => $_POST['company_id'],
            ':first'   => $_POST['first_name'],
            ':last'    => $_POST['last_name'],
            ':title'   => $_POST['title'],
            ':email'   => $_POST['email'],
            ':phone'   => $_POST['phone'],
            ':address' => $_POST['address'],
            ':id'      => $_POST['id']
        ]);
        header('Location: auth.php');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        header('Location: auth.php');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';

$query = "SELECT c.*, co.name AS company_name FROM customers c LEFT JOIN companies co ON c.company_id = co.id WHERE c.first_name LIKE :search1 OR c.last_name LIKE :search2 ORDER BY c.first_name $sort, c.last_name $sort";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':search1' => "%$search%",
    ':search2' => "%$search%"
]);
$customers = $stmt->fetchAll();
?>

<div class="container py-4">
    <h2 class="mb-4">Müşteriler</h2>
    <?php if (!$canAddCustomer): ?>
        <div class="alert alert-warning">
            Müşteri ekleyebilmek için önce
            <a href="company.php" class="alert-link">firma</a> eklemelisiniz.
        </div>
    <?php endif; ?>
    <div class="row mb-3">

        <div class="col-12 text-end">
            <button type="button" class="btn btn-dark me-2" data-bs-toggle="modal"
                data-bs-target="#filterModal">Filtrele</button>
            <?php if ($canAddCustomer): ?>
                <button type="button" class="btn btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#addModal">Müşteri
                    Ekle</button>
            <?php else: ?>
                <a href="company.php" class="btn btn-<?php echo get_color(); ?>">Firma Ekle</a>
            <?php endif; ?>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>İsim</th>
                <th>Soyisim</th>
                <th>Firma</th>
                <th>Ünvan</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th class="text-center" style="width:150px;">İşlemler</th>
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
                    <td class="text-center">
                        <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                            data-bs-target="#editModal<?php echo $customer['id']; ?>">Düzenle</button>
                        <form method="post" action="auth.php" style="display:inline-block"
                            onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $customer['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Müşteri Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" action="auth.php">
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
        </tbody>
    </table>
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
            <form method="post" action="auth.php">
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

<?php include 'includes/footer.php'; ?>