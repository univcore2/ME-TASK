<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-3">My Notes</h4>

<div class="card shadow-sm">
  <div class="card-body">
    <textarea id="notesPageText" class="form-control" rows="10" placeholder="Write notes..."></textarea>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <small class="text-muted" id="notesPageStatus">Not saved</small>
      <button class="btn btn-primary" id="notesPageSaveBtn"><i class="bi bi-save me-1"></i>Save</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
