<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadlineDate = trim($_POST['deadline_date'] ?? '');
$deadlineTime = trim($_POST['deadline_time'] ?? '');
$assignedTo = (int)($_POST['assigned_to'] ?? 0);
$createdBy = (int)($_SESSION['user_id'] ?? 0);

if ($title === '' || $assignedTo <= 0) {
  echo json_encode(['ok' => false, 'message' => 'Task title and assignee are required.']);
  exit;
}

try {
  $tableCheck = $conn->query("SHOW TABLES LIKE 'tasks'");
  if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo json_encode(['ok' => false, 'message' => "Table 'tasks' not found in database."]);
    exit;
  }

  $colResult = $conn->query("SHOW COLUMNS FROM tasks");
  $columns = [];
  while ($col = $colResult->fetch_assoc()) {
    $columns[$col['Field']] = true;
  }

  $insertCols = [];
  $values = [];
  $types = '';

  $push = function (string $column, string $type, $value) use (&$insertCols, &$values, &$types): void {
    $insertCols[] = $column;
    $values[] = $value;
    $types .= $type;
  };

  if (!isset($columns['title'])) {
    echo json_encode(['ok' => false, 'message' => "Column 'title' is required on tasks table."]);
    exit;
  }

  $push('title', 's', $title);

  if (isset($columns['description'])) $push('description', 's', $description);
  if (isset($columns['assigned_to'])) $push('assigned_to', 'i', $assignedTo);
  if (isset($columns['created_by'])) $push('created_by', 'i', $createdBy);
  if (isset($columns['status'])) $push('status', 's', 'pending');
  if (isset($columns['progress'])) $push('progress', 'i', 0);

  $hasDeadline = ($deadlineDate !== '');
  $timePart = $deadlineTime !== '' ? $deadlineTime : '23:59';

  if ($hasDeadline && isset($columns['deadline'])) {
    $push('deadline', 's', $deadlineDate . ' ' . $timePart . ':00');
  }
  if ($hasDeadline && isset($columns['deadline_at'])) {
    $push('deadline_at', 's', $deadlineDate . ' ' . $timePart . ':00');
  }
  if ($hasDeadline && isset($columns['due_date'])) {
    $push('due_date', 's', $deadlineDate);
  }
  if ($hasDeadline && isset($columns['due_time'])) {
    $push('due_time', 's', $timePart . ':00');
  }
  if ($hasDeadline && isset($columns['deadline_date'])) {
    $push('deadline_date', 's', $deadlineDate);
  }
  if ($hasDeadline && isset($columns['deadline_time'])) {
    $push('deadline_time', 's', $timePart . ':00');
  }

  if (empty($insertCols)) {
    echo json_encode(['ok' => false, 'message' => 'No compatible columns found to create task.']);
    exit;
  }

  $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
  $sql = "INSERT INTO tasks (" . implode(',', $insertCols) . ") VALUES ($placeholders)";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$values);
  $stmt->execute();

  echo json_encode([
    'ok' => true,
    'message' => 'Task created successfully.',
    'task_id' => $stmt->insert_id
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Unable to create task right now.',
    'error' => $e->getMessage()
  ]);
}
