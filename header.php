<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$userName = 'Kullanıcı';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT isim, kullanici_adi FROM kullanicilar WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    if ($info) {
        $userName = $info['isim'] ?: $info['kullanici_adi'];
    }
}
?>
<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="dashboard">Teklif Pro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="company">Firmalar</a></li>
                <li class="nav-item"><a class="nav-link" href="auth">Müşteriler</a></li>
                <li class="nav-item"><a class="nav-link" href="product">Ürünler</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="teklifDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">Teklifler</a>
                    <ul class="dropdown-menu" aria-labelledby="teklifDropdown">
                        <li><a class="dropdown-item" href="offer/genel">Genel</a></li>
                        <li><a class="dropdown-item" href="offer/giyotin">Giyotin</a></li>
                        <li><a class="dropdown-item" href="offer/surme">Sürme</a></li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="themeToggle">Tema</button>
                <div class="dropdown">
                    <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="userMenu"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($userName) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="profil">Profil</a></li>
                        <li><a class="dropdown-item" href="ayarlar">Ayarlar</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout">Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<script>
// tema değiştirici - seçimi sakla
const toggleBtn = document.getElementById('themeToggle');
const navbar = document.getElementById('mainNavbar');

function applyTheme(mode) {
    if (mode === 'dark') {
        document.body.classList.add('bg-dark', 'text-white');
        if (navbar) {
            navbar.classList.remove('navbar-light', 'bg-light');
            navbar.classList.add('navbar-dark', 'bg-dark');
        }
    } else {
        document.body.classList.remove('bg-dark', 'text-white');
        if (navbar) {
            navbar.classList.remove('navbar-dark', 'bg-dark');
            navbar.classList.add('navbar-light', 'bg-light');
        }
    }
}

const saved = localStorage.getItem('theme') || 'light';
applyTheme(saved);

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const newTheme = document.body.classList.contains('bg-dark') ? 'light' : 'dark';
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
}
</script>