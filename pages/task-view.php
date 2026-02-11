<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Task #<?= $taskId ?></h4>
  <a href="/pages/tasks.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body" id="taskDetail">
        <div class="text-muted">Task details will load here via AJAX.</div>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i>Timeline</div>
      <div class="card-body" id="taskTimeline">
        <div class="text-muted">Updates will load here.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-graph-up me-1"></i>Update Progress</div>
      <div class="card-body">
        <label class="form-label">Progress (%)</label>
        <input type="range" class="form-range" min="0" max="100" id="progressRange">
        <div class="d-flex justify-content-between">
          <small class="text-muted">0</small>
          <small class="fw-semibold" id="progressValue">0%</small>
          <small class="text-muted">100</small>
        </div>

        <label class="form-label mt-3">Status</label>
        <select class="form-select" id="statusSelect">
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>

        <button class="btn btn-primary w-100 mt-3" id="saveProgressBtn">
          <i class="bi bi-save me-1"></i>Save Update
        </button>

        <div class="mt-3" id="taskUpdateMsg"></div>
      </div>
    </div>
  </div>
</div>

<script>
  // expose task id for app.js
  window.ME_TASK_ID = <?= (int)$taskId ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
