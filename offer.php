<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
load_theme_settings($pdo);

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_giyotin') {
        $stmt = $pdo->prepare("INSERT INTO guillotine_quotes (system_type, width_mm, height_mm, system_qty) VALUES (:type, :width, :height, :qty)");
        $stmt->execute([
            ':type' => $_POST['system_type'],
            ':width' => $_POST['width_mm'],
            ':height' => $_POST['height_mm'],
            ':qty' => $_POST['system_qty']
        ]);
        header('Location: offer.php');
        exit;
    } elseif ($action === 'add_surme') {
        $stmt = $pdo->prepare("INSERT INTO sliding_quotes (system_type, width_mm, height_mm, system_qty) VALUES (:type, :width, :height, :qty)");
        $stmt->execute([
            ':type' => $_POST['system_type'],
            ':width' => $_POST['width_mm'],
            ':height' => $_POST['height_mm'],
            ':qty' => $_POST['system_qty']
        ]);
        header('Location: offer.php');
        exit;
    } elseif ($action === 'delete') {
        if ($_POST['type'] === 'giyotin') {
            $stmt = $pdo->prepare("DELETE FROM guillotine_quotes WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM sliding_quotes WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
        }
        header('Location: offer.php');
        exit;
    }
}

$giyotin = $pdo->query("SELECT id, system_type, width_mm, height_mm, created_at FROM guillotine_quotes ORDER BY created_at DESC")->fetchAll();
$surme   = $pdo->query("SELECT id, system_type, width_mm, height_mm, created_at FROM sliding_quotes ORDER BY created_at DESC")->fetchAll();
$quotes = [];
foreach ($giyotin as $q) {
    $q['type'] = 'giyotin';
    $quotes[] = $q;
}
foreach ($surme as $q) {
    $q['type'] = 'surme';
    $quotes[] = $q;
}
usort($quotes, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
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
<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-<?php echo get_color(); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Teklif Ekle
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addGiyotinModal">Giyotin</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addSurmeModal">Sürme</a></li>
                </ul>
            </div>
        </div>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tür</th>
                <th>Genişlik (mm)</th>
                <th>Yükseklik (mm)</th>
                <th>Tarih</th>
                <th class="text-center" style="width:150px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($quotes as $quote): ?>
            <tr>
                <td><?php echo htmlspecialchars($quote['type']); ?></td>
                <td><?php echo htmlspecialchars($quote['width_mm']); ?></td>
                <td><?php echo htmlspecialchars($quote['height_mm']); ?></td>
                <td><?php echo htmlspecialchars($quote['created_at']); ?></td>
                <td class="text-center">
                    <form method="post" style="display:inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                        <input type="hidden" name="type" value="<?php echo $quote['type']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Giyotin Modal -->
<div class="modal fade" id="addGiyotinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Giyotin Teklif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_giyotin">
                    <input type="hidden" name="system_type" value="Giyotin">
                    <div class="mb-3">
                        <label class="form-label">Genişlik (mm)</label>
                        <input type="number" name="width_mm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yükseklik (mm)</label>
                        <input type="number" name="height_mm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sistem Adedi</label>
                        <input type="number" name="system_qty" class="form-control" value="1" required>
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
<!-- Sürme Modal -->
<div class="modal fade" id="addSurmeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sürme Teklif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_surme">
                    <input type="hidden" name="system_type" value="Sürme">
                    <div class="mb-3">
                        <label class="form-label">Genişlik (mm)</label>
                        <input type="number" name="width_mm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yükseklik (mm)</label>
                        <input type="number" name="height_mm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sistem Adedi</label>
                        <input type="number" name="system_qty" class="form-control" value="1" required>
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
</body>
</html>
