<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userName = $_SESSION['user_name'] ?? 'Guest';
$userRole = $_SESSION['user_role'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?></title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE_URL ?>pages/dashboard.php">
      <i class="bi bi-check2-square me-1"></i><?= APP_NAME ?>
    </a>

    <div class="ms-auto d-flex align-items-center gap-3 text-white">
      <div>
        <div class="fw-semibold"><?= htmlspecialchars($userName) ?></div>
        <small><?= htmlspecialchars($userRole) ?></small>
      </div>

      <?php if (!empty($_SESSION['user_id'])): ?>
        <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>auth/logout.php">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row">
