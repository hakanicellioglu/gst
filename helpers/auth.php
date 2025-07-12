<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the currently logged in user has the admin role.
 */
function is_admin(PDO $pdo): bool
{
    $userId = $_SESSION['user']['id'] ?? null;
    if ($userId === null) {
        return false;
    }
    try {
        $stmt = $pdo->prepare(
            'SELECT r.name FROM roles r ' .
            'JOIN role_user ru ON r.id = ru.role_id ' .
            'WHERE ru.user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        return $role === 'admin';
    } catch (PDOException $e) {
        return false;
    }
}
?>
