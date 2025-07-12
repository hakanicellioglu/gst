<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Log create/update/delete actions to the audit_logs table.
 */
function audit_log(PDO $pdo, string $table, $recordId, string $action): void
{
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
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, table_name, record_id, action_id) VALUES (:user, :table_name, :record_id, :action_id)'
        );
        $stmt->execute([
            ':user' => $userId,
            ':table_name' => $table,
            ':record_id' => $recordId,
            ':action_id' => $actionId,
        ]);
    } catch (PDOException $e) {
        // Do not interrupt application flow if audit logging fails
    }
}
