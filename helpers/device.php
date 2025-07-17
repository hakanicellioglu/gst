<?php
function is_mobile(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua) === 1;
}

/**
 * Determine list view type based on query parameters and device.
 * Defaults to 'card' on mobile when no view is specified.
 */
function resolve_view(): string
{
    return $_GET['view'] ?? (is_mobile() ? 'card' : 'list');
}
