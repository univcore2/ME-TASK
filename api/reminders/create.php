<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$title = trim($_POST['title'] ?? '');
$details = trim($_POST['details'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$repeat = $_POST['repeat_type'] ?? 'none';
$vis = $_POST['visibility'] ?? 'personal';

if ($title === '' || $date === '' || $time === '') {
  echo json_encode(['ok'=>false,'message'=>'Title, date and time required']); exit;
}
if (!in_array($repeat, ['none','daily','weekly','monthly'], true)) $repeat = 'none';
if (!in_array($vis, ['personal','team','all'], true)) $vis = 'personal';

$dt = $date . ' ' . $time . ':00';
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO reminders (title, details, remind_datetime, repeat_type, visibility, created_by, is_done)
                        VALUES (?,?,?,?,?, ?,0)");
$stmt->bind_param("sssssi", $title, $details, $dt, $repeat, $vis, $userId);
$stmt->execute();

echo json_encode(['ok'=>true,'message'=>'Reminder created']);
