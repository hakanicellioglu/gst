<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $color = $_POST['color'] ?? 'primary';
    set_theme($theme);
    set_color($color);
    $success = 'Ayarlar kaydedildi.';
}

$theme = get_theme();
$color = get_color();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ayarlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <form method="post">
                            <div class="mb-3">
                                <label for="theme" class="form-label">Tema</label>
                                <select id="theme" name="theme" class="form-select">
                                    <option value="light" <?php echo $theme === 'light' ? 'selected' : ''; ?>>Açık
                                    </option>
                                    <option value="dark" <?php echo $theme === 'dark' ? 'selected' : ''; ?>>Koyu</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="color" class="form-label">Renk</label>
                                <select id="color" name="color" class="form-select">
                                    <?php foreach (['primary', 'success', 'danger', 'warning', 'info'] as $c): ?>
                                        <option value="<?php echo $c; ?>" <?php echo $color === $c ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>