<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/audit.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$errors = [];
$success = '';
load_theme_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $color = $_POST['color'] ?? 'primary';
    set_theme($theme);
    set_color($color);

    try {
        $pdo->beginTransaction();
        // Save theme setting
        $stmt = $pdo->prepare(
            "INSERT INTO settings (user_id, `key`, value) " .
            "VALUES (:user, 'theme', :val) " .
            "ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute([
            ':user' => $_SESSION['user']['id'],
            ':val'  => json_encode($theme)
        ]);

        // Save color setting
        $stmt = $pdo->prepare(
            "INSERT INTO settings (user_id, `key`, value) " .
            "VALUES (:user, 'color', :val) " .
            "ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute([
            ':user' => $_SESSION['user']['id'],
            ':val'  => json_encode($color)
        ]);
        $pdo->commit();
        audit_log($pdo, 'settings', $_SESSION['user']['id'], 'update');
        $success = 'Ayarlar kaydedildi.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = 'Ayarları kaydederken hata oluştu: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ayarlar</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tema Ayarları</h5>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($errors): ?>
                            <div class="alert alert-danger">
                                <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                            </div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label" for="theme">Tema</label>
                                <select id="theme" name="theme" class="form-select">
                                    <option value="light" <?php echo get_theme() === 'light' ? 'selected' : ''; ?>>Aydınlık</option>
                                    <option value="dark" <?php echo get_theme() === 'dark' ? 'selected' : ''; ?>>Karanlık</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="color">Renk</label>
                                <select id="color" name="color" class="form-select">
                                    <?php
                                    $colors = ['primary','secondary','success','danger','warning','info','light','dark'];
                                    foreach ($colors as $c) {
                                        $sel = get_color() === $c ? 'selected' : '';
                                        echo "<option value=\"$c\" $sel>" . ucfirst($c) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body></html>
