<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure default action records exist so foreign key constraints do not fail.
 */
function ensure_default_actions(PDO $pdo): void
{
    $sql = "INSERT IGNORE INTO actions (id, name) VALUES
            (1, 'login'),
            (2, 'logout'),
            (3, 'create'),
            (4, 'update'),
            (5, 'delete')";
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Silently ignore if actions table is missing or insert fails
    }
}

function logAction(PDO $pdo, string $table, $recordId, string $action, $oldValue = null, $newValue = null): void
{
    $allowed = ['create', 'update', 'delete'];
    $action = strtolower($action);
    if (!in_array($action, $allowed, true)) {
        return;
    }
    audit_log($pdo, $table, $recordId, $action, $oldValue, $newValue);
}

function audit_log(PDO $pdo, string $table, $recordId, string $action, $oldValue = null, $newValue = null): void
{
    if (is_array($oldValue) || is_object($oldValue)) {
        $oldValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE);
    }
    if (is_array($newValue) || is_object($newValue)) {
        $newValue = json_encode($newValue, JSON_UNESCAPED_UNICODE);
    }
    // Ensure action IDs exist for foreign key constraint
    ensure_default_actions($pdo);
    $userId = $_SESSION['user']['id'] ?? null;
    $actionMap = [
        'create' => 3,
        'update' => 4,
        'delete' => 5,
    ];
    $actionId = $actionMap[strtolower($action)] ?? null;
    if ($actionId === null) {
        return;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, table_name, record_id, old_value, new_value, action_id) VALUES (:user, :table_name, :record_id, :old_value, :new_value, :action_id)'
        );
        $stmt->execute([
            ':user' => $userId,
            ':table_name' => $table,
            ':record_id' => $recordId,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
            ':action_id' => $actionId,
        ]);
    } catch (PDOException $e) {
        // Do not interrupt application flow if audit logging fails
    }
}
