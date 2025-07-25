<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize a string by trimming and escaping special characters.
 */
function sanitize_string(string $value): string
{
    $value = trim($value);
    return filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

/**
 * Validate an email address and return sanitized email or null on failure.
 */
function validate_email(string $email): ?string
{
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    return $email ?: null;
}

/**
 * Validate an integer and return it or null.
 */
function validate_int($value): ?int
{
    $result = filter_var($value, FILTER_VALIDATE_INT);
    return ($result !== false) ? (int)$result : null;
}

/**
 * Validate a float and return it or null.
 */
function validate_float($value): ?float
{
    $result = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($result !== false) ? (float)$result : null;
}
?>
