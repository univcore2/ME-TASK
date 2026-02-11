<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['ok'=>false,'message'=>'Invalid user']); exit; }

$stmt = $conn->prepare("UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['ok'=>true,'message'=>'Status updated']);
