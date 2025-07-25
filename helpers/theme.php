<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_theme(string $theme): void
{
    $_SESSION['theme'] = $theme;
}

function get_theme(): string
{
    return $_SESSION['theme'] ?? 'light';
}

function theme_css(): string
{
    return (get_theme() === 'dark')
        ? 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/darkly/bootstrap.min.css'
        : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';
}

/**
 * Store the preferred UI color in the session.
 */
function set_color(string $color): void
{
    $_SESSION['color'] = $color;
}

/**
 * Get the preferred UI color.
 */
function get_color(): string
{
    return $_SESSION['color'] ?? 'primary';
}

/**
 * Load theme and color settings from the database if not already in session.
 */
function load_theme_settings(PDO $pdo): void
{
    if (!isset($_SESSION['user']['id'])) {
        return;
    }
    $query = "SELECT `key`, value FROM settings
              WHERE user_id = ? AND `key` IN ('theme','color')";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user']['id']]);
        foreach ($stmt as $row) {
            $value = json_decode($row['value'], true);
            if ($row['key'] === 'theme' && !isset($_SESSION['theme'])) {
                set_theme($value);
            }
            if ($row['key'] === 'color' && !isset($_SESSION['color'])) {
                set_color($value);
            }
        }
    } catch (PDOException $e) {
        // Silently ignore if settings table or query fails
    }
}