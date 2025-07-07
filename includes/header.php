<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers/theme.php';
?>
<link href="<?php echo theme_css(); ?>" rel="stylesheet">
<nav
    class="navbar navbar-expand-lg <?php echo get_theme() === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-light'; ?>">
    <div class="container">
        <a class="navbar-brand" href="dashboard">GST
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="company.php">Firmalar</a></li>
                <li class="nav-item"><a class="nav-link" href="customers.php">Müşteriler</a></li>
                <li class="nav-item"><a class="nav-link" href="product.php">Ürünler</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="offerDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Teklifler
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="offerDropdown">
                        <li><a class="dropdown-item" href="offer/giyotin.php">Giyotin</a></li>
                        <li><a class="dropdown-item" href="offer/surme.php">Sürme</a></li>
                        <li><a class="dropdown-item" href="offer/general.php">Genel</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Kullanıcı'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profili Düzenle</a></li>
                        <li><a class="dropdown-item" href="settings.php">Ayarlar</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>