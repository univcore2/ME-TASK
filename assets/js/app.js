// assets/js/app.js
document.addEventListener("DOMContentLoaded", () => {

  const buildCandidates = (path) => {
    const cleanedPath = path.replace(/^\/+/, '');
    const baseEl = document.querySelector('link[href*="assets/css/app.css"]');
    const baseHref = baseEl ? baseEl.getAttribute('href') : '';
    const baseMatch = baseHref.match(/^(.*)assets\/css\/app\.css$/);
    const base = baseMatch ? baseMatch[1] : '/';

    return [
      `${base}${cleanedPath}`,
      `../${cleanedPath}`,
      `/${cleanedPath}`
    ];
  };

  const fetchJsonWithFallback = async (path, options = {}) => {
    const candidates = buildCandidates(path);
    let lastError = null;

    for (const candidate of candidates) {
      try {
        const response = await fetch(candidate, {
          credentials: "same-origin",
          ...options
        });

        if (response.status === 404) continue;

        const contentType = response.headers.get("content-type") || "";
        if (!contentType.includes("application/json")) {
          const body = await response.text();
          throw new Error(`Expected JSON but got ${contentType || 'unknown'} (${response.status}). Body starts with: ${body.slice(0, 80)}`);
        }

        return await response.json();
      } catch (error) {
        lastError = error;
      }
    }

    throw lastError || new Error('Request failed for all known URL paths.');
  };

  const escapeHtml = (text = '') => String(text)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  // Quick Note Save (Dashboard)
  const saveNoteBtn = document.getElementById("saveNoteBtn");
  if (saveNoteBtn) {
    saveNoteBtn.addEventListener("click", async () => {
      const status = document.getElementById("noteStatus");
      status.textContent = "Saving...";

      setTimeout(() => {
        status.textContent = "Saved ✓";
      }, 400);
    });
  }

  // Tasks table page
  const tasksTableBody = document.getElementById('tasksTableBody');
  if (tasksTableBody) {
    const filterStatus = document.getElementById('filterStatus');
    const filterUser = document.getElementById('filterUser');
    const searchBox = document.getElementById('searchBox');

    const renderTasks = (tasks = []) => {
      if (!Array.isArray(tasks) || tasks.length === 0) {
        tasksTableBody.innerHTML = '<tr><td colspan="6" class="text-muted">No tasks found.</td></tr>';
        return;
      }

      tasksTableBody.innerHTML = tasks.map((task) => {
        const status = task.status || 'pending';
        const badgeClass = status === 'completed'
          ? 'bg-success-subtle text-success'
          : status === 'in_progress'
            ? 'bg-warning-subtle text-warning'
            : 'bg-secondary-subtle text-secondary';

        const progress = Number(task.progress || 0);
        const safeProgress = Number.isFinite(progress) ? Math.max(0, Math.min(100, progress)) : 0;
        const deadline = task.deadline ? escapeHtml(task.deadline) : '-';
        const taskId = Number(task.id || 0);

        return `
          <tr>
            <td>
              <div class="fw-semibold">${escapeHtml(task.title || '-')}</div>
              <div class="small text-muted text-truncate" style="max-width:320px;">${escapeHtml(task.description || '')}</div>
            </td>
            <td>${escapeHtml(task.assigned_name || 'Unassigned')}</td>
            <td>${deadline}</td>
            <td><span class="badge ${badgeClass}">${escapeHtml(status.replace('_', ' '))}</span></td>
            <td>
              <div class="progress" role="progressbar" aria-label="Task progress" aria-valuenow="${safeProgress}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: ${safeProgress}%">${safeProgress}%</div>
              </div>
            </td>
            <td class="text-end">
              ${taskId > 0 ? `<a class="btn btn-sm btn-outline-primary" href="task-view.php?id=${taskId}"><i class="bi bi-eye"></i></a>` : '-'}
            </td>
          </tr>
        `;
      }).join('');
    };

    const loadTasks = async () => {
      tasksTableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';

      const params = new URLSearchParams();
      if (filterStatus && filterStatus.value) params.set('status', filterStatus.value);
      if (filterUser && filterUser.value) params.set('user', filterUser.value);
      if (searchBox && searchBox.value.trim()) params.set('q', searchBox.value.trim());

      try {
        const data = await fetchJsonWithFallback(`api/tasks/list.php?${params.toString()}`);
        if (!data.ok) {
          tasksTableBody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(data.message || 'Failed to load tasks.')}</td></tr>`;
          return;
        }
        renderTasks(data.tasks || []);
      } catch (err) {
        console.error(err);
        tasksTableBody.innerHTML = '<tr><td colspan="6" class="text-danger">Error loading tasks.</td></tr>';
      }
    };

    const loadUsersFilter = async () => {
      if (!filterUser) return;
      try {
        const data = await fetchJsonWithFallback('api/users/list_simple.php');
        if (!data.ok || !Array.isArray(data.users)) return;

        filterUser.innerHTML = '<option value="all">All Users</option>';
        data.users.forEach((user) => {
          const opt = document.createElement('option');
          opt.value = user.id;
          opt.textContent = user.name;
          filterUser.appendChild(opt);
        });
      } catch (err) {
        console.error('Unable to load user filter:', err);
      }
    };

    loadUsersFilter().then(loadTasks);

    if (filterStatus) filterStatus.addEventListener('change', loadTasks);
    if (filterUser) filterUser.addEventListener('change', loadTasks);
    if (searchBox) {
      let searchTimer;
      searchBox.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(loadTasks, 250);
      });
    }
  }

  // Progress slider (Task View)
  const progressRange = document.getElementById("progressRange");
  const progressValue = document.getElementById("progressValue");
  if (progressRange && progressValue) {
    progressRange.addEventListener("input", () => {
      progressValue.textContent = `${progressRange.value}%`;
    });
  }

});
