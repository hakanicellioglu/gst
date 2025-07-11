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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firmalar</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

// Handle Add Company
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO companies (name, phone, address, email) VALUES (:name, :phone, :address, :email)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address'],
            ':email' => $_POST['email']
        ]);
        header('Location: company');
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE companies SET name = :name, phone = :phone, address = :address, email = :email WHERE id = :id");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address'],
            ':email' => $_POST['email'],
            ':id' => $_POST['id']
        ]);
        header('Location: company');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        header('Location: company');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'DESC' : 'ASC';
$view = $_GET['view'] ?? 'list';

$p = $_GET;
$p['view'] = 'list';
$listUrl = 'company?' . http_build_query($p);
$p['view'] = 'card';
$cardUrl = 'company?' . http_build_query($p);

$query = "SELECT * FROM companies WHERE name LIKE :search ORDER BY name $sort";
$stmt = $pdo->prepare($query);
$stmt->execute([':search' => "%$search%"]);
$companies = $stmt->fetchAll();
?>

<div class="container py-4">
    <h2 class="mb-4">Firmalar</h2>
    <div class="row mb-3">
        <div class="col-12 text-end">
            <form method="get" class="d-inline-block me-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Firma ara" style="display:inline-block;width:auto;">
                <input type="hidden" name="sort" value="<?php echo strtolower($sort); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <button type="submit" class="btn btn-<?php echo get_color(); ?> ms-2">Ara</button>
            </form>
            <form method="get" class="d-inline-block me-2">
                <select name="sort" class="form-select d-inline-block w-auto">
                    <option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>A'dan Z'ye</option>
                    <option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Z'den A'ya</option>
                </select>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            </form>
            <button type="button" class="btn btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#addModal">Firma
                Ekle</button>
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
                <th>Ad</th>
                <th>Telefon</th>
                <th>Adres</th>
                <th>E-posta</th>
                <th class="text-center" style="width:150px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                    <td><?php echo htmlspecialchars($company['phone']); ?></td>
                    <td><?php echo htmlspecialchars($company['address']); ?></td>
                    <td><?php echo htmlspecialchars($company['email']); ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal"
                            data-bs-target="#editModal<?php echo $company['id']; ?>">Düzenle</button>
                        <form method="post" action="company" style="display:inline-block"
                            onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $company['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Firma Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" action="company">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Ad</label>
                                        <input type="text" name="name"
                                            value="<?php echo htmlspecialchars($company['name']); ?>" class="form-control"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="phone"
                                            value="<?php echo htmlspecialchars($company['phone']); ?>" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Adres</label>
                                        <input type="text" name="address"
                                            value="<?php echo htmlspecialchars($company['address']); ?>"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" name="email"
                                            value="<?php echo htmlspecialchars($company['email']); ?>" class="form-control">
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
    <?php else: ?>
    <div class="row">
        <?php foreach ($companies as $company): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($company['name']); ?></h5>
                        <p class="card-text">
                            Telefon: <?php echo htmlspecialchars($company['phone']); ?><br>
                            Adres: <?php echo htmlspecialchars($company['address']); ?><br>
                            E-posta: <?php echo htmlspecialchars($company['email']); ?>
                        </p>
                        <div class="text-end">
                            <button class="btn btn-sm btn-<?php echo get_color(); ?>" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $company['id']; ?>">Düzenle</button>
                            <form method="post" action="company" style="display:inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?php echo $company['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Firma Düzenle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="company">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Ad</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Adres</label>
                                    <input type="text" name="address" value="<?php echo htmlspecialchars($company['address']); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($company['email']); ?>" class="form-control">
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
            <form method="get" action="company">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Firma ara</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            class="form-control">
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
                    <button type="submit" class="btn btn-<?php echo get_color(); ?>">Uygula</button>
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
                <h5 class="modal-title">Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="company">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control">
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
