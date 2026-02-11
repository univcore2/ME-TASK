<?php
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Clear all session values
$_SESSION = [];

// Destroy session cookie (if exists)
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: " . BASE_URL . "auth/login.php");
exit;
