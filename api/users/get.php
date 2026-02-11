<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo json_encode(['ok'=>false,'message'=>'Invalid user id']); exit;
}

$stmt = $conn->prepare("SELECT id, name, email, role, is_active FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
  echo json_encode(['ok'=>false,'message'=>'User not found']); exit;
}

echo json_encode(['ok'=>true,'user'=>$user]);
