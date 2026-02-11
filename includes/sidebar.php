<?php require_once __DIR__ . '/config.php'; ?>

<aside class="col-12 col-lg-2 bg-white border-end min-vh-100 p-0">
  <div class="list-group list-group-flush p-3">

    <a class="list-group-item list-group-item-action"
       href="<?= BASE_URL ?>pages/dashboard.php">
       <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>

    <a class="list-group-item list-group-item-action"
       href="<?= BASE_URL ?>pages/tasks.php">
       <i class="bi bi-list-task me-2"></i>Tasks
    </a>

    <a class="list-group-item list-group-item-action"
       href="<?= BASE_URL ?>pages/task-create.php">
       <i class="bi bi-plus-circle me-2"></i>Create Task
    </a>

    <a class="list-group-item list-group-item-action"
       href="<?= BASE_URL ?>pages/reminders.php">
       <i class="bi bi-calendar2-event me-2"></i>Reminders
    </a>

    <a class="list-group-item list-group-item-action"
       href="<?= BASE_URL ?>pages/notes.php">
       <i class="bi bi-journal-text me-2"></i>Notes
    </a>
    <a class="list-group-item list-group-item-action" 
        href="<?= BASE_URL ?>pages/users.php">
        <i class="bi bi-people me-2"></i>Users
</a>

  </div>
</aside>

<main class="col-12 col-lg-10 p-4">
