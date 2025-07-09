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

$errors = [];
$success = '';
$userId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($firstName === '' || $lastName === '' || $username === '' || $email === '') {
        $errors[] = 'Tüm alanları doldurun.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Kullanıcı adı veya e-posta zaten kullanılıyor.';
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $update = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, username=?, email=?, password_hash=? WHERE id=?');
                    $update->execute([
                        $firstName,
                        $lastName,
                        $username,
                        $email,
                        $hash,
                        $userId
                    ]);
                } else {
                    $update = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, username=?, email=? WHERE id=?');
                    $update->execute([
                        $firstName,
                        $lastName,
                        $username,
                        $email,
                        $userId
                    ]);
                }
                $_SESSION['user']['first_name'] = $firstName;
                $_SESSION['user']['last_name']  = $lastName;
                $_SESSION['user']['username']   = $username;
                $_SESSION['user']['email']      = $email;
                $success = 'Profil güncellendi.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

$current = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profili Düzenle</h5>
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
                                <label class="form-label" for="first_name">İsim</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($current['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="last_name">Soyisim</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($current['last_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="username">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($current['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($current['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Yeni Parola</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Parolayı değiştirmek için doldurun">
                            </div>
                            <button type="submit" class="btn btn-<?php echo get_color(); ?>">Güncelle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body></html>
