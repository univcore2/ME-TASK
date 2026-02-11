<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$done = $_GET['done'] ?? '0'; // 0 pending default
$visibility = $_GET['visibility'] ?? 'all';

$userId = (int)($_SESSION['user_id'] ?? 0);

$sql = "SELECT id, title, details, remind_datetime, repeat_type, visibility, created_by, is_done
        FROM reminders WHERE 1=1";
$params = [];
$types = "";

// Visibility rules:
// - personal: show only own reminders
// - team/all: show to all users
if ($visibility === 'personal') {
  $sql .= " AND visibility='personal' AND created_by=?";
  $params[] = $userId; $types .= "i";
} elseif ($visibility === 'team') {
  $sql .= " AND visibility='team'";
} elseif ($visibility === 'all') {
  $sql .= " AND visibility='all'";
} else {
  // all filter -> show: own personal + team + all
  $sql .= " AND ( (visibility='personal' AND created_by=?) OR visibility IN ('team','all') )";
  $params[] = $userId; $types .= "i";
}

if ($q !== '') {
  $sql .= " AND title LIKE ?";
  $params[] = "%$q%";
  $types .= "s";
}

if ($done !== 'all') {
  $sql .= " AND is_done=?";
  $params[] = (int)$done;
  $types .= "i";
}

$sql .= " ORDER BY remind_datetime ASC LIMIT 200";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$now = time();

while ($r = $result->fetch_assoc()) {
  $ts = strtotime($r['remind_datetime']);
  $r['is_overdue'] = ($ts !== false && $ts < $now) ? 1 : 0;
  $r['remind_datetime_display'] = $r['remind_datetime']; // keep simple (we can format later)
  $rows[] = $r;
}

echo json_encode(['ok'=>true,'reminders'=>$rows]);
