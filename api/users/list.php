<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? 'all';
$active = $_GET['active'] ?? 'all';

$sql = "SELECT id, name, email, role, is_active, created_at FROM users WHERE 1=1";
$params = [];
$types = "";

if ($q !== '') {
  $sql .= " AND (name LIKE ? OR email LIKE ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like;
  $types .= "ss";
}
if ($role !== 'all') {
  $sql .= " AND role = ?";
  $params[] = $role;
  $types .= "s";
}
if ($active !== 'all') {
  $sql .= " AND is_active = ?";
  $params[] = (int)$active;
  $types .= "i";
}

$sql .= " ORDER BY role ASC, name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;

echo json_encode(['ok' => true, 'users' => $users]);
