<?php
require_once __DIR__ . '/../includes/db.php';

$pass = "Marun@12345"; // change
$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?,?,?,?,1)");
$name = "Admin";
$email = "admin@eposter.com"; // change
$role = "admin";
$stmt->bind_param("ssss", $name, $email, $hash, $role);
$stmt->execute();

echo "Admin created. Email: $email Password: $pass";
