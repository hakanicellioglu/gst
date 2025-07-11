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
include 'includes/header.php';
?>
<div class="container py-5 h-min">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-4 mb-4 text-<?php echo get_color(); ?>">Hoş geldin,
                <?php echo isset($_SESSION['user']['first_name']) ? htmlspecialchars($_SESSION['user']['first_name']) : "Kullanıcı!"; ?>
            </h1>
            <?php if (isset($_SESSION['token'])): ?>
                <p class="text-muted">Token: <code><?php echo htmlspecialchars($_SESSION['token']); ?></code></p>
            <?php endif; ?>
        </div>
    </div>
</div>
