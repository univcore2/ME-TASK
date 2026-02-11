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

  const NOTES_KEY = 'me_task_manager_notes';

  // Notes (Dashboard + Notes Page)
  const quickNote = document.getElementById('quickNote');
  const noteStatus = document.getElementById('noteStatus');
  const saveNoteBtn = document.getElementById('saveNoteBtn');

  const notesPageText = document.getElementById('notesPageText');
  const notesPageStatus = document.getElementById('notesPageStatus');
  const notesPageSaveBtn = document.getElementById('notesPageSaveBtn');

  const loadSavedNotes = () => {
    try {
      const saved = localStorage.getItem(NOTES_KEY) || '';
      if (quickNote) quickNote.value = saved;
      if (notesPageText) notesPageText.value = saved;
      if (noteStatus) noteStatus.textContent = saved ? 'Saved ✓' : 'Not saved';
      if (notesPageStatus) notesPageStatus.textContent = saved ? 'Saved ✓' : 'Not saved';
    } catch (error) {
      console.error('Unable to read notes:', error);
    }
  };

  const saveNotes = () => {
    try {
      const text = quickNote ? quickNote.value : (notesPageText ? notesPageText.value : '');
      localStorage.setItem(NOTES_KEY, text);
      if (quickNote && notesPageText) {
        quickNote.value = text;
        notesPageText.value = text;
      }
      if (noteStatus) noteStatus.textContent = 'Saved ✓';
      if (notesPageStatus) notesPageStatus.textContent = 'Saved ✓';
    } catch (error) {
      console.error('Unable to save notes:', error);
      if (noteStatus) noteStatus.textContent = 'Save failed';
      if (notesPageStatus) notesPageStatus.textContent = 'Save failed';
    }
  };

  loadSavedNotes();

  if (saveNoteBtn) {
    saveNoteBtn.addEventListener('click', () => {
      if (noteStatus) noteStatus.textContent = 'Saving...';
      saveNotes();
    });
  }

  if (notesPageSaveBtn) {
    notesPageSaveBtn.addEventListener('click', () => {
      if (notesPageStatus) notesPageStatus.textContent = 'Saving...';
      saveNotes();
    });
  }

  if (quickNote) {
    quickNote.addEventListener('input', () => {
      if (noteStatus) noteStatus.textContent = 'Not saved';
    });
  }

  if (notesPageText) {
    notesPageText.addEventListener('input', () => {
      if (notesPageStatus) notesPageStatus.textContent = 'Not saved';
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

  // Dashboard widgets
  const pendingList = document.getElementById('pendingList');
  const progressList = document.getElementById('progressList');
  const completedList = document.getElementById('completedList');
  const reminderWidget = document.getElementById('reminderWidget');
  const workloadSummary = document.getElementById('workloadSummary');

  if (pendingList && progressList && completedList) {
    const renderTaskCards = (targetEl, tasks) => {
      if (!tasks.length) {
        targetEl.innerHTML = '<div class="text-muted small">No tasks.</div>';
        return;
      }

      targetEl.innerHTML = tasks.slice(0, 8).map((task) => {
        const deadline = task.deadline ? `<div class="small text-muted"><i class="bi bi-calendar-event me-1"></i>${escapeHtml(task.deadline)}</div>` : '';
        const taskId = Number(task.id || 0);

        return `
          <div class="border rounded p-2 mb-2">
            <div class="fw-semibold small">${escapeHtml(task.title || '-')}</div>
            <div class="small text-muted">${escapeHtml(task.assigned_name || 'Unassigned')}</div>
            ${deadline}
            ${taskId > 0 ? `<a class="small" href="task-view.php?id=${taskId}">Open</a>` : ''}
          </div>
        `;
      }).join('');
    };

    const renderWorkload = (tasks) => {
      if (!workloadSummary) return;
      if (!tasks.length) {
        workloadSummary.innerHTML = '<div class="text-muted small">No workload data.</div>';
        return;
      }

      const counts = {};
      tasks.forEach((task) => {
        const name = task.assigned_name || 'Unassigned';
        counts[name] = (counts[name] || 0) + 1;
      });

      const rows = Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .map(([name, count]) => `<div class="d-flex justify-content-between small"><span>${escapeHtml(name)}</span><span class="fw-semibold">${count}</span></div>`)
        .join('');

      workloadSummary.innerHTML = rows;
    };

    const loadDashboardTasks = async () => {
      try {
        const data = await fetchJsonWithFallback('api/tasks/list.php?status=all');
        if (!data.ok || !Array.isArray(data.tasks)) throw new Error(data.message || 'Failed to load tasks');

        const tasks = data.tasks;
        renderTaskCards(pendingList, tasks.filter((task) => (task.status || 'pending') === 'pending'));
        renderTaskCards(progressList, tasks.filter((task) => (task.status || '') === 'in_progress'));
        renderTaskCards(completedList, tasks.filter((task) => (task.status || '') === 'completed'));
        renderWorkload(tasks);
      } catch (error) {
        console.error(error);
        pendingList.innerHTML = '<div class="text-danger small">Unable to load tasks.</div>';
        progressList.innerHTML = '<div class="text-danger small">Unable to load tasks.</div>';
        completedList.innerHTML = '<div class="text-danger small">Unable to load tasks.</div>';
        if (workloadSummary) workloadSummary.innerHTML = '<div class="text-danger small">Unable to load summary.</div>';
      }
    };

    loadDashboardTasks();
  }

  if (reminderWidget) {
    const loadReminderWidget = async () => {
      reminderWidget.innerHTML = '<div class="text-muted small">Loading reminders...</div>';

      try {
        const data = await fetchJsonWithFallback('api/reminders/list.php?done=0&visibility=all');
        if (!data.ok || !Array.isArray(data.reminders)) throw new Error(data.message || 'Failed to load reminders');

        if (!data.reminders.length) {
          reminderWidget.innerHTML = '<div class="text-muted small">No upcoming reminders.</div>';
          return;
        }

        reminderWidget.innerHTML = data.reminders.slice(0, 5).map((reminder) => `
          <div class="border rounded p-2 mb-2">
            <div class="fw-semibold small">${escapeHtml(reminder.title || '-')}</div>
            <div class="small text-muted">${escapeHtml(reminder.remind_datetime_display || '')}</div>
          </div>
        `).join('');
      } catch (error) {
        console.error(error);
        reminderWidget.innerHTML = '<div class="text-danger small">Unable to load reminders.</div>';
      }
    };

    loadReminderWidget();
  }

  // Progress slider (Task View)
  const progressRange = document.getElementById("progressRange");
  const progressValue = document.getElementById("progressValue");

  const taskDetail = document.getElementById('taskDetail');
  const taskTimeline = document.getElementById('taskTimeline');
  const statusSelect = document.getElementById('statusSelect');
  const saveProgressBtn = document.getElementById('saveProgressBtn');
  const taskUpdateMsg = document.getElementById('taskUpdateMsg');

  if (taskDetail && taskTimeline && progressRange && progressValue && statusSelect && saveProgressBtn && typeof window.ME_TASK_ID !== 'undefined') {
    const taskId = Number(window.ME_TASK_ID || 0);

    const renderTaskDetail = (task) => {
      const progress = Number(task.progress || 0);
      const safeProgress = Number.isFinite(progress) ? Math.max(0, Math.min(100, progress)) : 0;
      const status = String(task.status || 'pending');

      taskDetail.innerHTML = `
        <h5 class="mb-1">${escapeHtml(task.title || '-')}</h5>
        <div class="small text-muted mb-3">Assigned to: ${escapeHtml(task.assigned_name || 'Unassigned')}</div>
        <p class="mb-3">${escapeHtml(task.description || 'No description provided.')}</p>

        <div class="row g-2 small">
          <div class="col-12 col-md-6"><span class="text-muted">Deadline:</span> ${escapeHtml(task.deadline || '-')}</div>
          <div class="col-12 col-md-6"><span class="text-muted">Status:</span> ${escapeHtml(status.replace('_', ' '))}</div>
          <div class="col-12 col-md-6"><span class="text-muted">Progress:</span> ${safeProgress}%</div>
          <div class="col-12 col-md-6"><span class="text-muted">Last update:</span> ${escapeHtml(task.updated_at || task.created_at || '-')}</div>
        </div>
      `;

      progressRange.value = String(safeProgress);
      progressValue.textContent = `${safeProgress}%`;
      statusSelect.value = ['pending', 'in_progress', 'completed'].includes(status) ? status : 'pending';
    };

    const renderTimeline = (task) => {
      taskTimeline.innerHTML = `
        <div class="small border-start ps-3 mb-2">
          <div class="fw-semibold">Task created</div>
          <div class="text-muted">${escapeHtml(task.created_at || '-')}</div>
        </div>
        <div class="small border-start ps-3">
          <div class="fw-semibold">Latest status: ${escapeHtml(String(task.status || 'pending').replace('_', ' '))}</div>
          <div class="text-muted">Progress ${escapeHtml(String(task.progress || 0))}% • ${escapeHtml(task.updated_at || task.created_at || '-')}</div>
        </div>
      `;
    };

    const loadTaskDetails = async () => {
      if (taskId <= 0) {
        taskDetail.innerHTML = '<div class="text-danger">Invalid task id.</div>';
        taskTimeline.innerHTML = '<div class="text-muted">No timeline available.</div>';
        return;
      }

      try {
        const data = await fetchJsonWithFallback(`api/tasks/get.php?id=${taskId}`);
        if (!data.ok || !data.task) {
          taskDetail.innerHTML = `<div class="text-danger">${escapeHtml(data.message || 'Unable to load task.')}</div>`;
          taskTimeline.innerHTML = '<div class="text-muted">No timeline available.</div>';
          return;
        }

        renderTaskDetail(data.task);
        renderTimeline(data.task);
      } catch (error) {
        console.error(error);
        taskDetail.innerHTML = '<div class="text-danger">Error loading task details.</div>';
        taskTimeline.innerHTML = '<div class="text-muted">No timeline available.</div>';
      }
    };

    saveProgressBtn.addEventListener('click', async () => {
      if (taskUpdateMsg) taskUpdateMsg.innerHTML = '<div class="text-muted small">Saving...</div>';

      try {
        const formBody = new URLSearchParams({
          id: String(taskId),
          status: statusSelect.value,
          progress: String(progressRange.value)
        });

        const data = await fetchJsonWithFallback('api/tasks/update_progress.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formBody
        });

        if (!data.ok) {
          if (taskUpdateMsg) taskUpdateMsg.innerHTML = `<div class="alert alert-danger py-2">${escapeHtml(data.message || 'Update failed.')}</div>`;
          return;
        }

        if (taskUpdateMsg) taskUpdateMsg.innerHTML = `<div class="alert alert-success py-2">${escapeHtml(data.message || 'Task updated.')}</div>`;
        loadTaskDetails();
      } catch (error) {
        console.error(error);
        if (taskUpdateMsg) taskUpdateMsg.innerHTML = '<div class="alert alert-danger py-2">Error saving task update.</div>';
      }
    });

    loadTaskDetails();
  }

  if (progressRange && progressValue) {
    progressRange.addEventListener("input", () => {
      progressValue.textContent = `${progressRange.value}%`;
    });
  }

});
