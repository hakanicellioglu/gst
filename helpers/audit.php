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
            (1, 'create'),
            (2, 'update'),
            (3, 'delete')";
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Silently ignore if actions table is missing or insert fails
    }
}

function audit_log(PDO $pdo, string $table, $recordId, string $action, $oldValue = null, $newValue = null): void
{
    // Ensure action IDs exist for foreign key constraint
    ensure_default_actions($pdo);
    $userId = $_SESSION['user']['id'] ?? null;
    $actionMap = [
        'create' => 1,
        'update' => 2,
        'delete' => 3,
    ];
    $actionId = $actionMap[strtolower($action)] ?? null;
    if ($actionId === null) {
        return;
    }
    if (is_array($oldValue) || is_object($oldValue)) {
        $oldValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE);
    }
    if (is_array($newValue) || is_object($newValue)) {
        $newValue = json_encode($newValue, JSON_UNESCAPED_UNICODE);
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
