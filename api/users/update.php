<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'user';

if ($id <= 0 || $name === '' || $email === '') {
  echo json_encode(['ok'=>false,'message'=>'Invalid data']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['ok'=>false,'message'=>'Invalid email']); exit;
}
if (!in_array($role, ['admin','user'], true)) $role = 'user';

$stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
$stmt->bind_param("sssi", $name, $email, $role, $id);

try {
  $stmt->execute();
  echo json_encode(['ok'=>true,'message'=>'User updated successfully']);
} catch (mysqli_sql_exception $e) {
  if ($e->getCode() == 1062) {
    echo json_encode(['ok'=>false,'message'=>'Email already exists']);
  } else {
    echo json_encode(['ok'=>false,'message'=>'Failed to update user']);
  }
}
