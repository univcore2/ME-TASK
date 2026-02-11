<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$status = trim($_GET['status'] ?? 'all');
$user = (int)($_GET['user'] ?? 0);
$q = trim($_GET['q'] ?? '');

try {
  $tableCheck = $conn->query("SHOW TABLES LIKE 'tasks'");
  if (!$tableCheck || $tableCheck->num_rows === 0) {
    echo json_encode(['ok' => true, 'tasks' => []]);
    exit;
  }

  $colResult = $conn->query("SHOW COLUMNS FROM tasks");
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
  $createdCol = $pick(['created_at', 'created_on', 'id']);

  $deadlineExpr = "NULL";
  if (isset($columns['deadline'])) {
    $deadlineExpr = "t.deadline";
  } elseif (isset($columns['deadline_at'])) {
    $deadlineExpr = "t.deadline_at";
  } elseif (isset($columns['due_date']) && isset($columns['due_time'])) {
    $deadlineExpr = "CONCAT(t.due_date, ' ', t.due_time)";
  } elseif (isset($columns['deadline_date']) && isset($columns['deadline_time'])) {
    $deadlineExpr = "CONCAT(t.deadline_date, ' ', t.deadline_time)";
  } elseif (isset($columns['due_date'])) {
    $deadlineExpr = "t.due_date";
  } elseif (isset($columns['deadline_date'])) {
    $deadlineExpr = "t.deadline_date";
  }

  if (!$titleCol) {
    echo json_encode(['ok' => true, 'tasks' => []]);
    exit;
  }

  $select = [];
  $select[] = $idCol ? "t.$idCol AS id" : "0 AS id";
  $select[] = "t.$titleCol AS title";
  $select[] = $descCol ? "t.$descCol AS description" : "'' AS description";
  $select[] = $statusCol ? "t.$statusCol AS status" : "'pending' AS status";
  $select[] = $progressCol ? "t.$progressCol AS progress" : "0 AS progress";
  $select[] = "$deadlineExpr AS deadline";
  $select[] = $assignedCol ? "t.$assignedCol AS assigned_to" : "0 AS assigned_to";

  $join = '';
  if ($assignedCol) {
    $select[] = "COALESCE(u.name, 'Unassigned') AS assigned_name";
    $join = " LEFT JOIN users u ON u.id = t.$assignedCol";
  } else {
    $select[] = "'Unassigned' AS assigned_name";
  }

  $sql = "SELECT " . implode(', ', $select) . " FROM tasks t" . $join . " WHERE 1=1";
  $params = [];
  $types = '';

  if ($status !== '' && $status !== 'all' && $statusCol) {
    $sql .= " AND t.$statusCol = ?";
    $params[] = $status;
    $types .= 's';
  }

  if ($user > 0 && $assignedCol) {
    $sql .= " AND t.$assignedCol = ?";
    $params[] = $user;
    $types .= 'i';
  }

  if ($q !== '') {
    $like = "%$q%";
    if ($descCol) {
      $sql .= " AND (t.$titleCol LIKE ? OR t.$descCol LIKE ?)";
      $params[] = $like;
      $params[] = $like;
      $types .= 'ss';
    } else {
      $sql .= " AND t.$titleCol LIKE ?";
      $params[] = $like;
      $types .= 's';
    }
  }

  if ($createdCol) {
    $sql .= " ORDER BY t.$createdCol DESC";
  }

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare tasks query.');
  }

  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }

  $stmt->execute();
  $res = $stmt->get_result();

  $tasks = [];
  while ($row = $res->fetch_assoc()) {
    $tasks[] = $row;
  }

  echo json_encode(['ok' => true, 'tasks' => $tasks]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Unable to load tasks right now.'
  ]);
}
