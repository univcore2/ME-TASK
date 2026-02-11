<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "pages/dashboard.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = "Please enter email and password.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email.";
  } else {
    $stmt = $conn->prepare("SELECT id, name, email, password_hash, role, is_active FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // ✅ Hostinger-safe fetch (no get_result)
    $stmt->bind_result($id, $name, $db_email, $password_hash, $role, $is_active);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || !password_verify($password, $password_hash)) {
      $error = "Invalid email or password.";
    } elseif ((int)$is_active !== 1) {
      $error = "Your account is inactive. Please contact admin.";
    } else {
      $_SESSION['user_id'] = (int)$id;
      $_SESSION['user_name'] = $name;
      $_SESSION['user_email'] = $db_email;
      $_SESSION['user_role'] = $role;

      session_regenerate_id(true);

      header("Location: " . BASE_URL . "pages/dashboard.php");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> - Login</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
  <div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 col-lg-4">

      <div class="text-center mb-3">
        <div class="fs-4 fw-semibold">
          <i class="bi bi-check2-square me-1"></i><?= APP_NAME ?>
        </div>
        <div class="text-muted small">Sign in to continue</div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body p-4">

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2">
              <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="post" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-dark w-100" type="submit">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </button>
          </form>

          <div class="mt-3 small text-muted text-center">
            If you forgot password, contact Admin.
          </div>

        </div>
      </div>

      <div class="text-center mt-3 small text-muted">
        © <?= date('Y') ?> Marundeshwara
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
