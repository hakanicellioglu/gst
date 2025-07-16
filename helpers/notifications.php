<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_notification_pref(bool $enabled): void
{
    $_SESSION['notify_email'] = $enabled;
}

function get_notification_pref(): bool
{
    return $_SESSION['notify_email'] ?? true;
}

function load_notification_settings(PDO $pdo): void
{
    if (!isset($_SESSION['user']['id'])) {
        return;
    }
    $query = "SELECT value FROM settings WHERE user_id = ? AND `key` = 'notify_email'";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user']['id']]);
        $val = $stmt->fetchColumn();
        if ($val !== false && !isset($_SESSION['notify_email'])) {
            set_notification_pref((bool) json_decode($val, true));
        }
    } catch (PDOException $e) {
        // ignore errors
    }
}
?>
