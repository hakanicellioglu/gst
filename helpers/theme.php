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