<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Users (Admin)</h4>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
    <i class="bi bi-person-plus me-1"></i>Add User
  </button>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="row g-2 mb-3">
      <div class="col-12 col-md-4">
        <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search name/email...">
      </div>
      <div class="col-12 col-md-3">
        <select id="roleFilter" class="form-select form-select-sm">
          <option value="all">All Roles</option>
          <option value="admin">Admin</option>
          <option value="user">User</option>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <select id="activeFilter" class="form-select form-select-sm">
          <option value="all">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-outline-secondary btn-sm" id="refreshUsers">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">#</th>
            <th>Name</th>
            <th>Email</th>
            <th style="width:110px;">Role</th>
            <th style="width:110px;">Status</th>
            <th style="width:240px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody id="usersTbody">
          <tr><td colspan="6" class="text-muted">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="userForm">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="userId">

        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" name="name" id="userName" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" id="userEmail" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" id="userRole">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="mb-3" id="passwordWrap">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" id="userPassword" minlength="6">
          <div class="form-text">Min 6 characters. (Only required when adding new user)</div>
        </div>

        <div id="userFormMsg"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  window.ME_BASE_URL = "<?= BASE_URL ?>";
</script>

<script>
// USERS PAGE JS (AJAX)
(function(){
  const tbody = document.getElementById("usersTbody");
  const search = document.getElementById("userSearch");
  const roleFilter = document.getElementById("roleFilter");
  const activeFilter = document.getElementById("activeFilter");
  const refreshBtn = document.getElementById("refreshUsers");

  const addUserBtn = document.getElementById("addUserBtn");
  const userModalTitle = document.getElementById("userModalTitle");
  const userForm = document.getElementById("userForm");
  const userFormMsg = document.getElementById("userFormMsg");

  const userId = document.getElementById("userId");
  const userName = document.getElementById("userName");
  const userEmail = document.getElementById("userEmail");
  const userRole = document.getElementById("userRole");
  const userPassword = document.getElementById("userPassword");
  const passwordWrap = document.getElementById("passwordWrap");

  async function fetchJSON(url, options={}) {
    const res = await fetch(url, options);
    const data = await res.json();
    return data;
  }

  function badgeRole(role){
    return role === 'admin'
      ? `<span class="badge text-bg-dark">Admin</span>`
      : `<span class="badge text-bg-secondary">User</span>`;
  }

  function badgeActive(active){
    return active == 1
      ? `<span class="badge text-bg-success">Active</span>`
      : `<span class="badge text-bg-danger">Inactive</span>`;
  }

  async function loadUsers() {
    tbody.innerHTML = `<tr><td colspan="6" class="text-muted">Loading...</td></tr>`;

    const params = new URLSearchParams({
      q: search.value.trim(),
      role: roleFilter.value,
      active: activeFilter.value
    });

    const data = await fetchJSON(ME_BASE_URL + "api/users/list.php?" + params.toString());
    if (!data.ok) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${data.message || 'Failed to load'}</td></tr>`;
      return;
    }

    if (!data.users.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-muted">No users found.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.users.map((u, idx) => `
      <tr>
        <td>${idx+1}</td>
        <td class="fw-semibold">${escapeHtml(u.name)}</td>
        <td>${escapeHtml(u.email)}</td>
        <td>${badgeRole(u.role)}</td>
        <td>${badgeActive(u.is_active)}</td>
        <td class="text-end">
          <button class="btn btn-outline-primary btn-sm me-1" data-action="edit" data-id="${u.id}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-outline-warning btn-sm me-1" data-action="reset" data-id="${u.id}"><i class="bi bi-key"></i></button>
          <button class="btn btn-outline-${u.is_active==1?'danger':'success'} btn-sm" data-action="toggle" data-id="${u.id}">
            <i class="bi ${u.is_active==1?'bi-person-x':'bi-person-check'}"></i>
          </button>
        </td>
      </tr>
    `).join("");
  }

  function escapeHtml(str){
    return (str ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  // Add user button
  addUserBtn.addEventListener("click", () => {
    userModalTitle.textContent = "Add User";
    userFormMsg.innerHTML = "";
    userForm.reset();
    userId.value = "";
    passwordWrap.style.display = "block";
    userPassword.required = true;
  });

  // table actions
  tbody.addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const action = btn.dataset.action;
    const id = btn.dataset.id;

    if (action === "edit") {
      const data = await fetchJSON(ME_BASE_URL + "api/users/get.php?id=" + encodeURIComponent(id));
      if (!data.ok) return alert(data.message || "Failed to load user");
      userModalTitle.textContent = "Edit User";
      userFormMsg.innerHTML = "";
      userId.value = data.user.id;
      userName.value = data.user.name;
      userEmail.value = data.user.email;
      userRole.value = data.user.role;
      userPassword.value = "";
      passwordWrap.style.display = "none";
      userPassword.required = false;

      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("userModal"));
      modal.show();
    }

    if (action === "toggle") {
      if (!confirm("Change active/inactive status?")) return;
      const data = await fetchJSON(ME_BASE_URL + "api/users/toggle.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({id})
      });
      if (!data.ok) return alert(data.message || "Failed");
      loadUsers();
    }

    if (action === "reset") {
      const newPass = prompt("Enter new password (min 6 chars):");
      if (!newPass || newPass.length < 6) return;
      const data = await fetchJSON(ME_BASE_URL + "api/users/reset_password.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({id, password: newPass})
      });
      if (!data.ok) return alert(data.message || "Failed");
      alert("Password reset successfully.");
    }
  });

  // save form (add/edit)
  userForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    userFormMsg.innerHTML = "";

    const formData = new FormData(userForm);
    const isEdit = !!formData.get("id");

    const url = ME_BASE_URL + (isEdit ? "api/users/update.php" : "api/users/create.php");
    const data = await fetchJSON(url, { method:"POST", body: formData });

    if (!data.ok) {
      userFormMsg.innerHTML = `<div class="alert alert-danger py-2">${data.message || 'Failed'}</div>`;
      return;
    }
    userFormMsg.innerHTML = `<div class="alert alert-success py-2">${data.message || 'Saved'}</div>`;
    loadUsers();

    setTimeout(() => {
      bootstrap.Modal.getInstance(document.getElementById("userModal"))?.hide();
    }, 600);
  });

  [search, roleFilter, activeFilter].forEach(el => el.addEventListener("input", () => loadUsers()));
  refreshBtn.addEventListener("click", loadUsers);

  loadUsers();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
