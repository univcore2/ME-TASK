<?php
// pages/reminders.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Reminders</h4>
  <button class="btn btn-primary btn-sm" id="addReminderBtn">
    <i class="bi bi-plus-circle me-1"></i>Add Reminder
  </button>
</div>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="row g-2 mb-3">
      <div class="col-12 col-md-4">
        <input type="text" id="remSearch" class="form-control form-control-sm" placeholder="Search reminder title...">
      </div>
      <div class="col-6 col-md-2">
        <select id="remDone" class="form-select form-select-sm">
          <option value="0">Pending</option>
          <option value="1">Done</option>
          <option value="all">All</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select id="remVisibility" class="form-select form-select-sm">
          <option value="all">All visibility</option>
          <option value="personal">Personal</option>
          <option value="team">Team</option>
          <option value="all_vis">Everyone</option>
        </select>
      </div>
      <div class="col-12 col-md-3 d-grid">
        <button class="btn btn-outline-secondary btn-sm" id="refreshReminders">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
      </div>
    </div>

    <div id="remindersList" class="list-group">
      <div class="text-muted">Loading...</div>
    </div>

  </div>
</div>

<!-- Add/Edit Reminder Modal -->
<div class="modal fade" id="reminderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="reminderForm">
      <div class="modal-header">
        <h5 class="modal-title" id="remModalTitle">Add Reminder</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="remId">

        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" id="remTitle" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Details (optional)</label>
          <textarea name="details" id="remDetails" class="form-control" rows="3"></textarea>
        </div>

        <div class="row g-2">
          <div class="col-6">
            <label class="form-label">Date</label>
            <input type="date" name="date" id="remDate" class="form-control" required>
          </div>
          <div class="col-6">
            <label class="form-label">Time</label>
            <input type="time" name="time" id="remTime" class="form-control" required>
          </div>
        </div>

        <div class="row g-2 mt-2">
          <div class="col-6">
            <label class="form-label">Repeat</label>
            <select name="repeat_type" id="remRepeat" class="form-select">
              <option value="none">None</option>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Visibility</label>
            <select name="visibility" id="remVis" class="form-select">
              <option value="personal">Personal</option>
              <option value="team">Team</option>
              <option value="all">Everyone</option>
            </select>
          </div>
        </div>

        <div id="remFormMsg" class="mt-3"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const BASE = "<?= BASE_URL ?>";

  const listEl = document.getElementById("remindersList");
  const btnAdd = document.getElementById("addReminderBtn");
  const btnRefresh = document.getElementById("refreshReminders");

  const searchEl = document.getElementById("remSearch");
  const doneEl = document.getElementById("remDone");
  const visFilterEl = document.getElementById("remVisibility");

  const modalEl = document.getElementById("reminderModal");
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

  const form = document.getElementById("reminderForm");
  const msg = document.getElementById("remFormMsg");

  const remId = document.getElementById("remId");
  const remTitle = document.getElementById("remTitle");
  const remDetails = document.getElementById("remDetails");
  const remDate = document.getElementById("remDate");
  const remTime = document.getElementById("remTime");
  const remRepeat = document.getElementById("remRepeat");
  const remVis = document.getElementById("remVis");
  const remModalTitle = document.getElementById("remModalTitle");

  function esc(str){
    return (str ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function visLabel(v){
    if (v === 'personal') return 'Personal';
    if (v === 'team') return 'Team';
    if (v === 'all') return 'Everyone';
    return v;
  }

  function normalizeVisFilter(v){
    // UI uses all_vis for "Everyone" to avoid value clash with "all"
    if (v === 'all_vis') return 'all';
    return v;
  }

  async function fetchJSON(url, options={}) {
    const res = await fetch(url, { credentials:"same-origin", ...options });
    return await res.json();
  }

  function buildItem(r){
    const due = r.remind_datetime_display ?? '';
    const badge = r.is_done == 1
      ? `<span class="badge text-bg-success">Done</span>`
      : `<span class="badge text-bg-warning">Pending</span>`;

    const repeat = r.repeat_type && r.repeat_type !== 'none'
      ? `<span class="badge text-bg-light border ms-2">${esc(r.repeat_type)}</span>`
      : '';

    const visibility = `<span class="badge text-bg-light border ms-2">${esc(visLabel(r.visibility))}</span>`;

    const overdue = r.is_overdue == 1 && r.is_done == 0
      ? `<span class="badge text-bg-danger ms-2">Overdue</span>` : '';

    return `
      <div class="list-group-item d-flex justify-content-between align-items-start">
        <div class="me-3">
          <div class="fw-semibold">
            ${esc(r.title)} ${badge} ${repeat} ${visibility} ${overdue}
          </div>
          ${r.details ? `<div class="text-muted small mt-1">${esc(r.details)}</div>` : ``}
          <div class="small mt-1"><i class="bi bi-clock me-1"></i>${esc(due)}</div>
        </div>

        <div class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" data-action="edit" data-id="${r.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-outline-${r.is_done==1?'secondary':'success'}" data-action="toggle" data-id="${r.id}">
              <i class="bi ${r.is_done==1?'bi-arrow-counterclockwise':'bi-check2'}"></i>
            </button>
            <button class="btn btn-outline-danger" data-action="delete" data-id="${r.id}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
  }

  async function loadReminders(){
    listEl.innerHTML = `<div class="text-muted">Loading...</div>`;

    const params = new URLSearchParams({
      q: searchEl.value.trim(),
      done: doneEl.value,
      visibility: normalizeVisFilter(visFilterEl.value)
    });

    const data = await fetchJSON(BASE + "api/reminders/list.php?" + params.toString());
    if (!data.ok){
      listEl.innerHTML = `<div class="text-danger">${esc(data.message || 'Failed')}</div>`;
      return;
    }

    if (!data.reminders.length){
      listEl.innerHTML = `<div class="text-muted">No reminders found.</div>`;
      return;
    }

    listEl.innerHTML = data.reminders.map(buildItem).join('');
  }

  function setDefaultDateTime(){
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth()+1).padStart(2,'0');
    const dd = String(now.getDate()).padStart(2,'0');
    remDate.value = `${yyyy}-${mm}-${dd}`;
    remTime.value = `09:00`;
  }

  btnAdd.addEventListener("click", () => {
    remModalTitle.textContent = "Add Reminder";
    msg.innerHTML = "";
    form.reset();
    remId.value = "";
    setDefaultDateTime();
    remRepeat.value = "none";
    remVis.value = "personal";
    modal.show();
  });

  btnRefresh.addEventListener("click", loadReminders);
  [searchEl, doneEl, visFilterEl].forEach(el => el.addEventListener("input", loadReminders));

  // list button actions
  listEl.addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;

    const id = btn.dataset.id;
    const action = btn.dataset.action;

    if (action === "edit"){
      const data = await fetchJSON(BASE + "api/reminders/get.php?id=" + encodeURIComponent(id));
      if (!data.ok) return alert(data.message || "Failed");

      const r = data.reminder;

      remModalTitle.textContent = "Edit Reminder";
      msg.innerHTML = "";

      remId.value = r.id;
      remTitle.value = r.title;
      remDetails.value = r.details || '';
      remRepeat.value = r.repeat_type || 'none';
      remVis.value = r.visibility || 'personal';

      // split datetime to date/time (expects yyyy-mm-dd hh:mm:ss)
      const dt = (r.remind_datetime || '').replace('T', ' ');
      const parts = dt.split(' ');
      remDate.value = parts[0] || '';
      remTime.value = (parts[1] || '').substring(0,5) || '';

      modal.show();
    }

    if (action === "toggle"){
      const data = await fetchJSON(BASE + "api/reminders/toggle_done.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({id})
      });
      if (!data.ok) return alert(data.message || "Failed");
      loadReminders();
    }

    if (action === "delete"){
      if (!confirm("Delete this reminder?")) return;
      const data = await fetchJSON(BASE + "api/reminders/delete.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({id})
      });
      if (!data.ok) return alert(data.message || "Failed");
      loadReminders();
    }
  });

  // save form
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    msg.innerHTML = `<div class="text-muted">Saving...</div>`;

    const fd = new FormData(form);
    const isEdit = !!fd.get("id");

    const url = BASE + (isEdit ? "api/reminders/update.php" : "api/reminders/create.php");
    const data = await fetchJSON(url, { method:"POST", body: fd });

    if (!data.ok){
      msg.innerHTML = `<div class="alert alert-danger py-2">${esc(data.message || 'Failed')}</div>`;
      return;
    }

    msg.innerHTML = `<div class="alert alert-success py-2">${esc(data.message || 'Saved')}</div>`;
    loadReminders();

    setTimeout(() => modal.hide(), 600);
  });

  loadReminders();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
