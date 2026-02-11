<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$details = trim($_POST['details'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$repeat = $_POST['repeat_type'] ?? 'none';
$vis = $_POST['visibility'] ?? 'personal';

if ($id <= 0 || $title === '' || $date === '' || $time === '') {
  echo json_encode(['ok'=>false,'message'=>'Invalid data']); exit;
}
if (!in_array($repeat, ['none','daily','weekly','monthly'], true)) $repeat = 'none';
if (!in_array($vis, ['personal','team','all'], true)) $vis = 'personal';

$userId = (int)$_SESSION['user_id'];
$dt = $date . ' ' . $time . ':00';

$stmt = $conn->prepare("SELECT created_by, visibility FROM reminders WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$rem = $stmt->get_result()->fetch_assoc();
if (!$rem) { echo json_encode(['ok'=>false,'message'=>'Not found']); exit; }

// personal reminders only editable by owner
if ($rem['visibility'] === 'personal' && (int)$rem['created_by'] !== $userId) {
  echo json_encode(['ok'=>false,'message'=>'Access denied']); exit;
}

$stmt2 = $conn->prepare("UPDATE reminders SET title=?, details=?, remind_datetime=?, repeat_type=?, visibility=? WHERE id=?");
$stmt2->bind_param("sssssi", $title, $details, $dt, $repeat, $vis, $id);
$stmt2->execute();

echo json_encode(['ok'=>true,'message'=>'Reminder updated']);
