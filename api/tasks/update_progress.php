<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$taskId = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? 'pending');
$progress = (int)($_POST['progress'] ?? 0);

if ($taskId <= 0) {
  echo json_encode(['ok' => false, 'message' => 'Invalid task id.']);
  exit;
}

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses, true)) {
  $status = 'pending';
}
$progress = max(0, min(100, $progress));

try {
  $tableCheck = $conn->query("SHOW TABLES LIKE 'tasks'");
  if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo json_encode(['ok' => false, 'message' => "Table 'tasks' not found."]);
    exit;
  }

  $colResult = $conn->query('SHOW COLUMNS FROM tasks');
  $columns = [];
  while ($col = $colResult->fetch_assoc()) {
    $columns[$col['Field']] = true;
  }

  $idCol = isset($columns['id']) ? 'id' : (isset($columns['task_id']) ? 'task_id' : null);
  if (!$idCol) {
    echo json_encode(['ok' => false, 'message' => 'Task table columns are not compatible.']);
    exit;
  }

  $set = [];
  $params = [];
  $types = '';

  if (isset($columns['status'])) {
    $set[] = 'status=?';
    $params[] = $status;
    $types .= 's';
  }

  if (isset($columns['task_status'])) {
    $set[] = 'task_status=?';
    $params[] = $status;
    $types .= 's';
  }

  if (isset($columns['progress'])) {
    $set[] = 'progress=?';
    $params[] = $progress;
    $types .= 'i';
  }

  if (isset($columns['updated_at'])) {
    $set[] = 'updated_at=NOW()';
  }
  if (isset($columns['updated_on'])) {
    $set[] = 'updated_on=NOW()';
  }

  if (empty($set)) {
    echo json_encode(['ok' => false, 'message' => 'No updatable task columns found.']);
    exit;
  }

  $params[] = $taskId;
  $types .= 'i';

  $sql = "UPDATE tasks SET " . implode(', ', $set) . " WHERE $idCol=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();

  if ($stmt->affected_rows < 0) {
    echo json_encode(['ok' => false, 'message' => 'Failed to update task.']);
    exit;
  }

  echo json_encode(['ok' => true, 'message' => 'Task updated successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Unable to update task right now.']);
}
