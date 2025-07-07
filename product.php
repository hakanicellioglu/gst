<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
load_theme_settings($pdo);
include 'includes/header.php';

// CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products (name, code) VALUES (:name, :code)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':code' => $_POST['code']
        ]);
        header('Location: product.php');
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE products SET name = :name, code = :code WHERE id = :id");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':code' => $_POST['code'],
            ':id' => $_POST['id']
        ]);
        header('Location: product.php');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        header('Location: product.php');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';

$query = "SELECT * FROM products WHERE name LIKE :search ORDER BY name $sort";
$stmt = $pdo->prepare($query);
$stmt->execute([':search' => "%$search%"]);
$products = $stmt->fetchAll();
?>

<div class="container py-4">
    <h2 class="mb-4">Ürünler</h2>
    <div class="row mb-3">
        <div class="col-12 text-end">
            <button type="button" class="btn btn-dark me-2" data-bs-toggle="modal"
                data-bs-target="#filterModal">Filtrele</button>
            <button type="button" class="btn btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#addModal">Ürün
                Ekle</button>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Ad</th>
                <th>Kod</th>
                <th class="text-center" style="width:150px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['code']); ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                            data-bs-target="#editModal<?php echo $product['id']; ?>">Düzenle</button>
                        <form method="post" action="product.php" style="display:inline-block"
                            onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Ürün Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" action="product.php">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Ad</label>
                                        <input type="text" name="name"
                                            value="<?php echo htmlspecialchars($product['name']); ?>" class="form-control"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kod</label>
                                        <input type="text" name="code"
                                            value="<?php echo htmlspecialchars($product['code']); ?>" class="form-control"
                                            required>
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
        <form class="modal-content" method="get" action="product.php">
            <div class="modal-header">
                <h5 class="modal-title">Filtrele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ara</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        class="form-control" placeholder="Ürün ara">
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
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ürün Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="product.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" required>
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