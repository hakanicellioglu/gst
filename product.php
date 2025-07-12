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

$categories = ['Alüminyum', 'Aksesuar', 'Fitil'];
$units = ['adet', 'metre', 'kg'];
$noMeasureUnits = ['adet', 'metre'];
$categoryUnitMap = [
    'Aksesuar' => 'adet',
    'Fitil' => 'metre',
    'Alüminyum' => 'kg'
];


// CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $category = $_POST['category'] ?? '';
    $unit = $_POST['unit'] ?? '';
    if (isset($categoryUnitMap[$category])) {
        $unit = $categoryUnitMap[$category];
    }
    $measureValue = $_POST['measure_value'] ?? 1;
    if (in_array($unit, $noMeasureUnits, true)) {
        $measureValue = 1;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products (name, code, unit, measure_value, unit_price, category) VALUES (:name, :code, :unit, :measure_value, :unit_price, :category)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':code' => $_POST['code'],
            ':unit' => $unit,
            ':measure_value' => $measureValue,
            ':unit_price' => $_POST['unit_price'],
            ':category' => $category
        ]);
        header('Location: product');
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE products SET name = :name, code = :code, unit = :unit, measure_value = :measure_value, unit_price = :unit_price, category = :category WHERE id = :id");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':code' => $_POST['code'],
            ':unit' => $unit,
            ':measure_value' => $measureValue,
            ':unit_price' => $_POST['unit_price'],
            ':category' => $category,
            ':id' => $_POST['id']
        ]);
        header('Location: product');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        header('Location: product');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';
$categoryFilter = $_GET['category'] ?? '';
$view = $_GET['view'] ?? 'list';

$paramList = $_GET;
$paramList['view'] = 'list';
$listUrl = 'product?' . http_build_query($paramList);
$paramList['view'] = 'card';
$cardUrl = 'product?' . http_build_query($paramList);

$query = "SELECT * FROM products WHERE name LIKE :search";
$params = [':search' => "%$search%"];
if ($categoryFilter !== '') {
    $query .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}
$query .= " ORDER BY name $sort";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ürünler</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Ürünler</h2>
    <div class="row mb-3">
        <div class="col-12 text-end">
            <form method="get" class="d-inline-block me-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    class="form-control" placeholder="Ürün ara" style="display:inline-block;width:auto;">
                <input type="hidden" name="sort" value="<?php echo strtolower($sort); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                <button type="submit" class="btn btn-<?php echo get_color(); ?> ms-2">Ara</button>
            </form>
            <form method="get" class="d-inline-block me-2">
                <select name="sort" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>A'dan Z'ye</option>
                    <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Z'den A'ya</option>
                </select>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
            </form>
            <button type="button" class="btn btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                data-bs-target="#addModal">Ürün
                Ekle</button>
            <div class="btn-group ms-2" role="group">
                <a href="<?php echo $listUrl; ?>" class="btn btn-outline-secondary <?php echo $view === 'list' ? 'active' : ''; ?>"><i class="bi bi-list"></i></a>
                <a href="<?php echo $cardUrl; ?>" class="btn btn-outline-secondary <?php echo $view === 'card' ? 'active' : ''; ?>"><i class="bi bi-grid"></i></a>



            </div>
            <a href="pdf.php?type=products" class="btn btn-outline-secondary ms-2">PDF</a>
        </div>

        <?php if ($view === 'list'): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Kod</th>
                        <th>Ölçü Birimi</th>
                        <th>Ölçü Değeri</th>
                        <th>Birim Fiyat</th>
                        <th>Kategori</th>
                        <th class="text-center" style="width:150px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['code']); ?></td>
                            <td><?php echo htmlspecialchars($product['unit']); ?></td>
                            <td><?php echo htmlspecialchars($product['measure_value']); ?></td>
                            <td><?php echo htmlspecialchars($product['unit_price']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?php echo $product['id']; ?>">Düzenle</button>
                                <form method="post" action="product" style="display:inline-block"
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
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form method="post" action="product">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Ad</label>
                                                <input type="text" name="name"
                                                    value="<?php echo htmlspecialchars($product['name']); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Kod</label>
                                                <input type="text" name="code"
                                                    value="<?php echo htmlspecialchars($product['code']); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Kategori</label>
                                                <select name="category" class="form-select category-select"
                                                    data-unit="unit<?php echo $product['id']; ?>">
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo $cat; ?>" <?php echo ($product['category'] === $cat) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3" id="unitWrapper<?php echo $product['id']; ?>">
                                                <label class="form-label">Ölçü Birimi</label>
                                                <select name="unit" id="unit<?php echo $product['id']; ?>"
                                                    class="form-select unit-select"
                                                    data-target="measure<?php echo $product['id']; ?>"
                                                    data-wrapper="unitWrapper<?php echo $product['id']; ?>" required>
                                                    <?php foreach ($units as $unitOption): ?>
                                                        <option value="<?php echo $unitOption; ?>" <?php echo ($product['unit'] === $unitOption) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($unitOption); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3" id="measureWrapper<?php echo $product['id']; ?>">
                                                <label class="form-label" id="measure<?php echo $product['id']; ?>Label">Ölçü
                                                    Değeri</label>
                                                <input type="number" step="0.001" name="measure_value"
                                                    id="measure<?php echo $product['id']; ?>"
                                                    value="<?php echo htmlspecialchars($product['measure_value']); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Birim Fiyat</label>
                                                <input type="number" step="0.01" name="unit_price"
                                                    value="<?php echo htmlspecialchars($product['unit_price']); ?>"
                                                    class="form-control" required>
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
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?>
                                    (<?php echo htmlspecialchars($product['code']); ?>)</h5>
                                <p class="card-text">
                                    Ölçü Birimi: <?php echo htmlspecialchars($product['unit']); ?><br>
                                    Ölçü Değeri: <?php echo htmlspecialchars($product['measure_value']); ?><br>
                                    Birim Fiyat: <?php echo htmlspecialchars($product['unit_price']); ?><br>
                                    Kategori: <?php echo htmlspecialchars($product['category']); ?>
                                </p>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $product['id']; ?>">Düzenle</button>
                                    <form method="post" action="product" style="display:inline-block"
                                        onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Ürün Düzenle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="product">
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
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select name="category" class="form-select category-select"
                                                data-unit="unit<?php echo $product['id']; ?>">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat; ?>" <?php echo ($product['category'] === $cat) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="unitWrapper<?php echo $product['id']; ?>">
                                            <label class="form-label">Ölçü Birimi</label>
                                            <select name="unit" id="unit<?php echo $product['id']; ?>"
                                                class="form-select unit-select"
                                                data-target="measure<?php echo $product['id']; ?>"
                                                data-wrapper="unitWrapper<?php echo $product['id']; ?>" required>
                                                <?php foreach ($units as $unitOption): ?>
                                                    <option value="<?php echo $unitOption; ?>" <?php echo ($product['unit'] === $unitOption) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($unitOption); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="measureWrapper<?php echo $product['id']; ?>">
                                            <label class="form-label" id="measure<?php echo $product['id']; ?>Label">Ölçü
                                                Değeri</label>
                                            <input type="number" step="0.001" name="measure_value"
                                                id="measure<?php echo $product['id']; ?>"
                                                value="<?php echo htmlspecialchars($product['measure_value']); ?>"
                                                class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Birim Fiyat</label>
                                            <input type="number" step="0.01" name="unit_price"
                                                value="<?php echo htmlspecialchars($product['unit_price']); ?>"
                                                class="form-control" required>
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
            <form class="modal-content" method="get" action="product">
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
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Hepsi</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($categoryFilter === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
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
                <form method="post" action="product">
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
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category" class="form-select category-select" data-unit="addUnit">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="addUnitWrapper">
                            <label class="form-label">Ölçü Birimi</label>
                            <select name="unit" id="addUnit" class="form-select unit-select" data-target="addMeasure"
                                data-wrapper="addUnitWrapper" required>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo $unit; ?>">
                                        <?php echo htmlspecialchars($unit); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="addMeasureWrapper">
                            <label class="form-label" id="addMeasureLabel">Ölçü Değeri</label>
                            <input type="number" step="0.001" name="measure_value" id="addMeasure" class="form-control"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birim Fiyat</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" required>
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
    <script>
        function updateMeasure(selectEl, measureInput, wrapper) {
            if (selectEl.value === 'adet' || selectEl.value === 'metre') {
                measureInput.value = 1;
                if (wrapper) wrapper.style.display = 'none';
                measureInput.removeAttribute('required');
            } else {
                if (wrapper) wrapper.style.display = '';
                measureInput.setAttribute('required', 'required');
            }
        }
        var categoryUnits = { 'Aksesuar': 'adet', 'Fitil': 'metre', 'Alüminyum': 'kg' };
        function updateUnitFromCategory(catSel) {
            var unitSelect = document.getElementById(catSel.getAttribute('data-unit'));
            if (!unitSelect) return;
            var targetUnit = categoryUnits[catSel.value];
            var unitWrapper = document.getElementById(unitSelect.getAttribute('data-wrapper'));
            if (targetUnit) {
                unitSelect.value = targetUnit;
            }
            var hideUnit = (catSel.value === 'Aksesuar' || catSel.value === 'Fitil' || catSel.value === 'Alüminyum');
            if (hideUnit) {
                if (unitWrapper) unitWrapper.style.display = 'none';
                unitSelect.removeAttribute('required');
            } else {
                if (unitWrapper) unitWrapper.style.display = '';
                unitSelect.setAttribute('required', 'required');
            }
            var measure = document.getElementById(unitSelect.getAttribute('data-target'));
            var wrapper = document.getElementById(unitSelect.getAttribute('data-target') + 'Wrapper');
            var label = document.getElementById(unitSelect.getAttribute('data-target') + 'Label');
            if (label) {
                label.textContent = (catSel.value === 'Alüminyum') ? 'Gramaj' : 'Ölçü Değeri';
            }
            updateMeasure(unitSelect, measure, wrapper);
        }
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.unit-select').forEach(function (sel) {
                var targetId = sel.getAttribute('data-target');
                var measure = document.getElementById(targetId);
                var wrapper = document.getElementById(targetId + 'Wrapper');
                updateMeasure(sel, measure, wrapper);
                sel.addEventListener('change', function () {
                    updateMeasure(sel, measure, wrapper);
                });
            });
            document.querySelectorAll('.category-select').forEach(function (cat) {
                updateUnitFromCategory(cat);
                cat.addEventListener('change', function () {
                    updateUnitFromCategory(cat);
                });
            });
        });
    </script>

</body>

</html>