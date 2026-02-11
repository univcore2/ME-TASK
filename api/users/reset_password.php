<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$pass = $_POST['password'] ?? '';

if ($id <= 0 || strlen($pass) < 6) {
  echo json_encode(['ok'=>false,'message'=>'Invalid data']); exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
$stmt->bind_param("si", $hash, $id);
$stmt->execute();

echo json_encode(['ok'=>true,'message'=>'Password reset successfully']);
