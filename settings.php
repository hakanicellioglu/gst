<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/audit.php';
require_once 'helpers/notifications.php';
require_once 'helpers/settings.php';
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

load_notification_settings($pdo);

$currencyOptions = ['USD', 'EUR', 'TRY', 'GBP', 'JPY'];
$timezoneOptions = timezone_identifiers_list();
$dateFormatOptions = ['Y-m-d H:i', 'd/m/Y H:i', 'm/d/Y h:i A', 'd.m.Y H:i'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $color = $_POST['color'] ?? 'primary';
    $notify = isset($_POST['notify_email']) ? (bool)$_POST['notify_email'] : true;
    $orderEmail = trim($_POST['order_email'] ?? '');
    $currency   = trim($_POST['currency'] ?? '');
    $timezone   = trim($_POST['timezone'] ?? '');
    $dateFormat = trim($_POST['date_format'] ?? '');
    set_theme($theme);
    set_color($color);
    set_notification_pref($notify);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO settings (user_id, `key`, value) VALUES (:user, :key, :val) " .
            "ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $pairs = [
            ['theme', $theme],
            ['color', $color],
            ['notify_email', $notify],
            ['order_email', $orderEmail],
            ['currency', $currency],
            ['timezone', $timezone],
            ['date_format', $dateFormat]
        ];
        foreach ($pairs as [$k, $v]) {
            $stmt->execute([
                ':user' => $_SESSION['user']['id'],
                ':key'  => $k,
                ':val'  => json_encode($v)
            ]);
        }
        $pdo->commit();
        logAction($pdo, 'settings', $_SESSION['user']['id'], 'update');
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
<body class="bg-light" data-bs-spy="scroll" data-bs-target="#settingsMenu" data-bs-offset="100" tabindex="0">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <div class="row">
            <nav id="settingsMenu" class="col-md-3 mb-4">
                <ul class="nav flex-column position-sticky" style="top:2rem">
                    <li class="nav-item"><a class="nav-link active" href="#theme-settings">Tema Ayarları</a></li>
                    <li class="nav-item"><a class="nav-link" href="#notification-settings">Bildirim Ayarları</a></li>
                    <li class="nav-item"><a class="nav-link" href="#app-settings">Genel Ayarlar</a></li>
                </ul>
            </nav>
            <div class="col-md-9">
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
                    <section id="theme-settings" class="card mb-5">
                        <div class="card-body">
                            <h5 class="card-title">Tema Ayarları</h5>
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
                        </div>
                    </section>
                    <section id="notification-settings" class="card mb-5">
                        <div class="card-body">
                            <h5 class="card-title">Bildirim Ayarları</h5>
                            <div class="mb-3">
                                <label class="form-label" for="notify_email">E-posta Bildirimleri</label>
                                <select id="notify_email" name="notify_email" class="form-select">
                                    <option value="1" <?php echo get_notification_pref() ? 'selected' : ''; ?>>Açık</option>
                                    <option value="0" <?php echo !get_notification_pref() ? 'selected' : ''; ?>>Kapalı</option>
                                </select>
                            </div>
                        </div>
                    </section>
                    <section id="app-settings" class="card mb-5">
                        <div class="card-body">
                            <h5 class="card-title">Genel Ayarlar</h5>
                            <div class="mb-3">
                                <label class="form-label" for="order_email">Sipariş E-posta</label>
                                <input type="email" id="order_email" name="order_email" class="form-control" value="<?php echo htmlspecialchars(get_setting($pdo, 'order_email')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="currency">Para Birimi</label>
                                <select id="currency" name="currency" class="form-select">
                                    <?php
                                    $currentCurrency = get_setting($pdo, 'currency');
                                    foreach ($currencyOptions as $cur) {
                                        $sel = $currentCurrency === $cur ? 'selected' : '';
                                        echo "<option value=\"$cur\" $sel>$cur</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="timezone">Zaman Dilimi</label>
                                <select id="timezone" name="timezone" class="form-select">
                                    <?php
                                    $currentTz = get_setting($pdo, 'timezone');
                                    foreach ($timezoneOptions as $tz) {
                                        $sel = $currentTz === $tz ? 'selected' : '';
                                        echo "<option value=\"$tz\" $sel>$tz</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="date_format">Tarih Formatı</label>
                                <select id="date_format" name="date_format" class="form-select">
                                    <?php
                                    $currentFormat = get_setting($pdo, 'date_format');
                                    foreach ($dateFormatOptions as $fmt) {
                                        $sel = $currentFormat === $fmt ? 'selected' : '';
                                        echo "<option value=\"$fmt\" $sel>" . date($fmt) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </section>
                    <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</body></html>
