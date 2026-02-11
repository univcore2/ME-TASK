<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
  echo json_encode(['ok' => false, 'message' => 'Invalid task id.']);
  exit;
}

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

  $pick = static function (array $options) use ($columns): ?string {
    foreach ($options as $name) {
      if (isset($columns[$name])) return $name;
    }
    return null;
  };

  $idCol = $pick(['id', 'task_id']);
  $titleCol = $pick(['title', 'task_title', 'name']);
  $descCol = $pick(['description', 'details']);
  $statusCol = $pick(['status', 'task_status']);
  $progressCol = $pick(['progress']);
  $assignedCol = $pick(['assigned_to', 'assignee_id', 'user_id']);
  $createdCol = $pick(['created_at', 'created_on']);
  $updatedCol = $pick(['updated_at', 'updated_on']);

  if (!$idCol || !$titleCol) {
    echo json_encode(['ok' => false, 'message' => 'Task table columns are not compatible.']);
    exit;
  }

  $deadlineExpr = 'NULL';
  if (isset($columns['deadline'])) {
    $deadlineExpr = 't.deadline';
  } elseif (isset($columns['deadline_at'])) {
    $deadlineExpr = 't.deadline_at';
  } elseif (isset($columns['due_date']) && isset($columns['due_time'])) {
    $deadlineExpr = "CONCAT(t.due_date, ' ', t.due_time)";
  } elseif (isset($columns['deadline_date']) && isset($columns['deadline_time'])) {
    $deadlineExpr = "CONCAT(t.deadline_date, ' ', t.deadline_time)";
  } elseif (isset($columns['due_date'])) {
    $deadlineExpr = 't.due_date';
  } elseif (isset($columns['deadline_date'])) {
    $deadlineExpr = 't.deadline_date';
  }

  $select = [];
  $select[] = "t.$idCol AS id";
  $select[] = "t.$titleCol AS title";
  $select[] = $descCol ? "t.$descCol AS description" : "'' AS description";
  $select[] = $statusCol ? "t.$statusCol AS status" : "'pending' AS status";
  $select[] = $progressCol ? "t.$progressCol AS progress" : '0 AS progress';
  $select[] = "$deadlineExpr AS deadline";
  $select[] = $createdCol ? "t.$createdCol AS created_at" : 'NULL AS created_at';
  $select[] = $updatedCol ? "t.$updatedCol AS updated_at" : 'NULL AS updated_at';

  $join = '';
  if ($assignedCol) {
    $select[] = "t.$assignedCol AS assigned_to";
    $select[] = "COALESCE(u.name, 'Unassigned') AS assigned_name";
    $join = " LEFT JOIN users u ON u.id = t.$assignedCol";
  } else {
    $select[] = '0 AS assigned_to';
    $select[] = "'Unassigned' AS assigned_name";
  }

  $sql = 'SELECT ' . implode(', ', $select) . " FROM tasks t" . $join . " WHERE t.$idCol = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException('Failed to prepare task detail query.');
  }

  $stmt->bind_param('i', $taskId);
  if (!$stmt->execute()) {
    throw new RuntimeException('Failed to execute task detail query.');
  }

  $stmt->bind_result(
    $id,
    $title,
    $description,
    $status,
    $progress,
    $deadline,
    $createdAt,
    $updatedAt,
    $assignedTo,
    $assignedName
  );

  $task = null;
  if ($stmt->fetch()) {
    $task = [
      'id' => $id,
      'title' => $title,
      'description' => $description,
      'status' => $status,
      'progress' => $progress,
      'deadline' => $deadline,
      'created_at' => $createdAt,
      'updated_at' => $updatedAt,
      'assigned_to' => $assignedTo,
      'assigned_name' => $assignedName,
    ];
  }

  $stmt->close();

  if (!$task) {
    echo json_encode(['ok' => false, 'message' => 'Task not found.']);
    exit;
  }

  echo json_encode(['ok' => true, 'task' => $task]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Unable to load task details right now.']);
}
