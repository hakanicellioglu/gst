<?php
require_once 'config.php';
require_once 'helpers/theme.php';
require_once 'helpers/template.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
load_theme_settings($pdo);

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Kullanıcı adı ve parola zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email']
                ];
                // Create a session token on successful login
                $token = bin2hex(random_bytes(32));
                $_SESSION['token'] = $token;
                setcookie('token', $token, time() + 3600, '/', '', false, true);
                header('Location: dashboard');
                exit;
            } else {
                $errors[] = 'Hatalı kullanıcı adı veya parola.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<?php
render('login', [
    'errors' => $errors,
    'username' => $username,
]);
?>
