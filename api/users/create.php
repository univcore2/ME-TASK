<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'user';
$pass = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $pass === '') {
  echo json_encode(['ok'=>false,'message'=>'Name, email and password required']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['ok'=>false,'message'=>'Invalid email']); exit;
}
if (strlen($pass) < 6) {
  echo json_encode(['ok'=>false,'message'=>'Password must be at least 6 characters']); exit;
}
if (!in_array($role, ['admin','user'], true)) $role = 'user';

$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?,?,?,?,1)");
$stmt->bind_param("ssss", $name, $email, $hash, $role);

try {
  $stmt->execute();
  echo json_encode(['ok'=>true,'message'=>'User created successfully']);
} catch (mysqli_sql_exception $e) {
  if ($e->getCode() == 1062) {
    echo json_encode(['ok'=>false,'message'=>'Email already exists']);
  } else {
    echo json_encode(['ok'=>false,'message'=>'Failed to create user']);
  }
}
