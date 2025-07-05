<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// helper for determining active link
function nav_active(string $target): string
{
    global $path;
    return $path === $target ? 'active border-primary border-bottom border-2' : '';
}

function nav_active_prefix(string $prefix): string
{
    global $path;
    return strpos($path, $prefix) === 0 ? 'active border-primary border-bottom border-2' : '';
}

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
                <li class="nav-item"><a class="nav-link <?php echo nav_active('company'); ?>"
                        href="company">Firmalar</a></li>
                <li class="nav-item"><a class="nav-link <?php echo nav_active('auth'); ?>" href="auth">Müşteriler</a>
                </li>
                <li class="nav-item"><a class="nav-link <?php echo nav_active('product'); ?>" href="product">Ürünler</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo nav_active_prefix('offer'); ?>" href="#"
                        id="teklifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Teklifler</a>
                    <ul class="dropdown-menu" aria-labelledby="teklifDropdown">
                        <li><a class="dropdown-item <?php echo nav_active('offer/genel'); ?>"
                                href="offer/genel">Genel</a></li>
                        <li><a class="dropdown-item <?php echo nav_active('offer/giyotin'); ?>"
                                href="offer/giyotin">Giyotin</a></li>
                        <li><a class="dropdown-item <?php echo nav_active('offer/surme'); ?>"
                                href="offer/surme">Sürme</a></li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3 d-flex align-items-center" id="themeToggle">
                    <i class="bi bi-moon-fill me-1" id="themeIcon"></i>
                    Tema
                </button>
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
    const themeIcon = document.getElementById('themeIcon');

    function applyTheme(mode) {
        if (mode === 'dark') {
            document.body.classList.add('bg-dark', 'text-white');
            if (navbar) {
                navbar.classList.remove('navbar-light', 'bg-light');
                navbar.classList.add('navbar-dark', 'bg-dark');
            }
            if (themeIcon) {
                themeIcon.classList.remove('bi-moon-fill');
                themeIcon.classList.add('bi-sun-fill');
            }
        } else {
            document.body.classList.remove('bg-dark', 'text-white');
            if (navbar) {
                navbar.classList.remove('navbar-dark', 'bg-dark');
                navbar.classList.add('navbar-light', 'bg-light');
            }
            if (themeIcon) {
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-fill');
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