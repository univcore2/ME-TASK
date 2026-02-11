<?php
// pages/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Dashboard</h4>
  <a href="<?= BASE_URL ?>/pages/task-create.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-circle me-1"></i>Create Task
  </a>
</div>

<div class="row g-3">
  <!-- Task Columns -->
  <div class="col-12 col-xl-9">
    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm task-col">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-hourglass-split me-1"></i>Pending
          </div>
          <div class="card-body" id="pendingList">
            <!-- AJAX will load tasks here -->
            <div class="text-muted small">No data loaded.</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card shadow-sm task-col">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-play-circle me-1"></i>In Progress
          </div>
          <div class="card-body" id="progressList">
            <div class="text-muted small">No data loaded.</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card shadow-sm task-col">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-check-circle me-1"></i>Completed
          </div>
          <div class="card-body" id="completedList">
            <div class="text-muted small">No data loaded.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Widgets (Reminder 1/8 + Notes 1/8) -->
  <div class="col-12 col-xl-3">
    <div class="widget-box d-flex flex-column gap-3">

      <!-- Reminder Widget -->
      <div class="card shadow-sm widget-card">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="bi bi-alarm me-1"></i>Reminders</span>
          <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>pages/reminders.php">View</a>
        </div>
        <div class="card-body p-2" id="reminderWidget">
          <div class="text-muted small">Upcoming reminders will show here.</div>
        </div>
      </div>

      <!-- Notes Widget -->
      <div class="card shadow-sm widget-card">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-journal-text me-1"></i>Quick Notes
        </div>
        <div class="card-body p-2">
          <textarea id="quickNote" class="form-control form-control-sm" rows="3" placeholder="Type and save notes..."></textarea>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted" id="noteStatus">Not saved</small>
            <button class="btn btn-primary btn-sm" id="saveNoteBtn">
              <i class="bi bi-save me-1"></i>Save
            </button>
          </div>
        </div>
      </div>

      <!-- Workload Summary -->
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-people me-1"></i>Workload Summary
        </div>
        <div class="card-body" id="workloadSummary">
          <div class="text-muted small">Counts by users will show here.</div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
