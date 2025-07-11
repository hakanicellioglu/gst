<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();
// Remove the token cookie when logging out
setcookie('token', '', time() - 3600, '/', '', false, true);

header('Location: /login');
exit;
?>