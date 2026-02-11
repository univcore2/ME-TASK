<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT created_by, visibility FROM reminders WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$rem = $stmt->get_result()->fetch_assoc();
if (!$rem) { echo json_encode(['ok'=>false,'message'=>'Not found']); exit; }

if ($rem['visibility'] === 'personal' && (int)$rem['created_by'] !== $userId) {
  echo json_encode(['ok'=>false,'message'=>'Access denied']); exit;
}

$conn->query("UPDATE reminders SET is_done = IF(is_done=1,0,1), done_at = IF(is_done=1, NULL, NOW()) WHERE id=".(int)$id);

echo json_encode(['ok'=>true,'message'=>'Updated']);
