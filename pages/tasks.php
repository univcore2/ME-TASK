<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">All Tasks</h4>
  <a href="<?= BASE_URL ?>pages/task-create.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="row g-2 mb-3">
      <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="filterStatus">
          <option value="all">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="filterUser">
          <option value="all">All Users</option>
          <!-- AJAX load users -->
        </select>
      </div>
      <div class="col-12 col-md-6">
        <input type="text" class="form-control form-control-sm" id="searchBox" placeholder="Search title/description...">
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Task</th>
            <th>Assigned To</th>
            <th>Deadline</th>
            <th>Status</th>
            <th style="width:160px;">Progress</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody id="tasksTableBody">
          <tr><td colspan="6" class="text-muted">No data loaded.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
