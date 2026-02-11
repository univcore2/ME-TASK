<?php
// pages/task-create.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-3">Create Task</h4>

<div class="card shadow-sm">
  <div class="card-body">

    <form id="createTaskForm" enctype="multipart/form-data" method="post">
      <div class="row g-3">

        <div class="col-12">
          <label class="form-label">Task Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>

        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"></textarea>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Deadline Date</label>
          <input type="date" name="deadline_date" class="form-control">
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Deadline Time</label>
          <input type="time" name="deadline_time" class="form-control">
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Assign To</label>
          <select name="assigned_to" class="form-select" id="assignedToSelect" required>
            <option value="">Loading users...</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Supporting Documents</label>
          <input type="file" name="files[]" class="form-control" multiple>
          <div class="form-text">You can upload multiple files (pdf/doc/jpg/png).</div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="<?= BASE_URL ?>pages/tasks.php" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-check2-circle me-1"></i>Create Task
          </button>
        </div>

      </div>
    </form>

    <div class="mt-3" id="createTaskMsg"></div>

  </div>
</div>

<!-- Load Users + Submit Task -->
<script>
document.addEventListener("DOMContentLoaded", function () {

  // 1) Load users for dropdown
  const select = document.getElementById("assignedToSelect");
  const url = "<?= BASE_URL ?>api/users/list_simple.php";

  fetch(url, { credentials: "same-origin" })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) {
        select.innerHTML = '<option value="">Failed to load users</option>';
        return;
      }

      select.innerHTML = '<option value="">Select user</option>';
      data.users.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = u.name;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("User load error:", err);
      select.innerHTML = '<option value="">Error loading users</option>';
    });


  // 2) Submit form (AJAX) - (API to be created: /api/tasks/create.php)
  const form = document.getElementById("createTaskForm");
  const msgBox = document.getElementById("createTaskMsg");

  form.addEventListener("submit", async function(e){
    e.preventDefault();
    msgBox.innerHTML = `<div class="text-muted">Saving...</div>`;

    try{
      const fd = new FormData(form);

      const res = await fetch("<?= BASE_URL ?>api/tasks/create.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin"
      });

      const data = await res.json();

      if(!data.ok){
        msgBox.innerHTML = `<div class="alert alert-danger py-2">${data.message || 'Failed to create task'}</div>`;
        return;
      }

      msgBox.innerHTML = `<div class="alert alert-success py-2">${data.message || 'Task created successfully'}</div>`;
      form.reset();

      // Optional redirect after success
      setTimeout(() => {
        window.location.href = "<?= BASE_URL ?>pages/tasks.php";
      }, 700);

    } catch(err){
      console.error(err);
      msgBox.innerHTML = `<div class="alert alert-danger py-2">Error while creating task.</div>`;
    }
  });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
