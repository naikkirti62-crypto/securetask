<?php
$page_title = 'New Task';
require_once __DIR__ . '/header.php';
require_login();

$user  = current_user();
$uid   = (int)$user['id'];
$error = '';
$edit  = get_int('edit');
$task  = null;

if ($edit) {
    $task = get_task($edit, $uid);
    if (!$task) {
        redirect('dashboard.php');
    }
    $page_title = 'Edit Task';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    guard_inputs($_POST);

    $title    = post('title');
    $desc     = post('description');
    $priority = post('priority');
    $status   = post('status', 'pending');
    $due      = post('due_date');
    $task_id  = post_int('task_id');

    // Validate
    if (empty($title)) {
        $error = 'Task title is required.';
    } elseif (strlen($title) > 200) {
        $error = 'Title must be 200 characters or fewer.';
    } elseif (!validate_enum($priority, ['low','medium','high'])) {
        $error = 'Invalid priority value.';
    } elseif ($due && !validate_date($due)) {
        $error = 'Invalid due date format.';
    } else {
        if ($task_id) {
            // Update existing
            update_task($task_id, $uid, $title, $desc, $priority, $status, $due);
            redirect('dashboard.php?updated=1');
        } else {
            // Create new
            create_task($uid, $title, $desc, $priority, $due);
            redirect('dashboard.php?created=1');
        }
    }
}

$f = $task ?? [
    'title'=>post('title'),'description'=>post('description'),
    'priority'=>post('priority','medium'),'status'=>'pending','due_date'=>''
];
?>

<main class="page-wrapper">
  <div class="page-header">
    <div>
      <h1 class="page-title"><?= $edit ? 'Edit Task' : 'New Task' ?></h1>
      <p class="page-subtitle"><?= $edit ? 'Update task details below.' : 'Fill in the details for your new task.' ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-ghost">← Back to Dashboard</a>
  </div>

  <div style="max-width:640px">
    <?php if ($error): ?><div class="alert alert-error">⚠ <?= esc($error) ?></div><?php endif; ?>

    <div class="card">
      <form method="POST" action="add_task.php<?= $edit ? "?edit={$edit}" : '' ?>">
        <?= csrf_field() ?>
        <?php if ($edit): ?>
          <input type="hidden" name="task_id" value="<?= $edit ?>">
        <?php endif; ?>

        <div class="form-group">
          <label for="title">Task Title <span style="color:var(--accent-rose)">*</span></label>
          <input type="text" id="title" name="title"
                 value="<?= esc($f['title']) ?>"
                 placeholder="What needs to be done?"
                 maxlength="200" required autofocus>
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description"
                    placeholder="Add more details, context, or notes…"
                    rows="4"><?= esc($f['description'] ?? '') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
              <option value="low"    <?= ($f['priority']==='low'   ?'selected':'')?>>🌱 Low</option>
              <option value="medium" <?= ($f['priority']==='medium'?'selected':'')?>>⚡ Medium</option>
              <option value="high"   <?= ($f['priority']==='high'  ?'selected':'')?>>🔥 High</option>
            </select>
          </div>

          <?php if ($edit): ?>
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
              <option value="pending"     <?= ($f['status']==='pending'    ?'selected':'')?>>⏳ Pending</option>
              <option value="in_progress" <?= ($f['status']==='in_progress'?'selected':'')?>>🔄 In Progress</option>
              <option value="completed"   <?= ($f['status']==='completed'  ?'selected':'')?>>✅ Completed</option>
            </select>
          </div>
          <?php else: ?>
          <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date"
                   value="<?= esc($f['due_date'] ?? '') ?>"
                   min="<?= date('Y-m-d') ?>">
          </div>
          <?php endif; ?>
        </div>

        <?php if ($edit): ?>
        <div class="form-group">
          <label for="due_date_edit">Due Date</label>
          <input type="date" id="due_date_edit" name="due_date"
                 value="<?= esc($f['due_date'] ?? '') ?>">
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:.75rem;margin-top:1rem">
          <button type="submit" class="btn btn-primary">
            <?= $edit ? '💾 Save Changes' : '+ Create Task' ?>
          </button>
          <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Security note -->
    <div class="card" style="margin-top:1rem;border-left:3px solid var(--accent-cyan)">
      <div style="display:flex;gap:.75rem;align-items:flex-start">
        <span style="font-size:1.3rem">🔐</span>
        <div>
          <div style="font-size:.85rem;font-weight:700;color:var(--text-primary);margin-bottom:.25rem">Security Active</div>
          <div style="font-size:.78rem;color:var(--text-muted);line-height:1.6;font-family:var(--font-mono)">
            CSRF token verified · Inputs sanitised · SQL-injection prevention active · User ownership validated
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>