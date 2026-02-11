<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, title, details, remind_datetime, repeat_type, visibility, created_by, is_done
                        FROM reminders WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$rem = $stmt->get_result()->fetch_assoc();

if (!$rem) { echo json_encode(['ok'=>false,'message'=>'Not found']); exit; }

// Access check: personal reminders only for owner
if ($rem['visibility'] === 'personal' && (int)$rem['created_by'] !== $userId) {
  echo json_encode(['ok'=>false,'message'=>'Access denied']); exit;
}

echo json_encode(['ok'=>true,'reminder'=>$rem]);
