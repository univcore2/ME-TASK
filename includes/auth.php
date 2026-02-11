<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
  if (empty($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
  }
}

function require_admin() {
  require_login();
  if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied.");
  }
}
