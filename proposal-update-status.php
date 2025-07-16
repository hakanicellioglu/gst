<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ge\xc3\xa7ersiz istek']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Eksik veri']);
    exit;
}

// TODO: Durumu veritaban\xc4\xb1na kaydet
// $stmt = $pdo->prepare("UPDATE master_quotes SET status=? WHERE id=?");
// $stmt->execute([$status, $id]);

echo json_encode(['success' => true, 'message' => 'Durum g\xc3\xbcncellendi']);

