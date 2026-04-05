<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/header.php';
require_login();

$user    = current_user();
$uid     = (int)$user['id'];
$stats   = task_stats($uid);

$filter_status   = get('status');
$filter_priority = get('priority');
$tasks           = get_tasks($uid, $filter_status, $filter_priority);

$progress = $stats['total'] > 0
    ? round(($stats['completed'] / $stats['total']) * 100)
    : 0;
?>

<main class="page-wrapper">
  <div class="page-header">
    <div>
      <h1 class="page-title">Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>,
        <?= esc($user['username']) ?> 👋
      </h1>
      <p class="page-subtitle">
        <?= $stats['total'] ?> tasks · <?= $progress ?>% complete
        <?php if ($stats['overdue'] > 0): ?>
          · <span class="text-rose">⚠ <?= $stats['overdue'] ?> overdue</span>
        <?php endif; ?>
      </p>
      <div class="progress-bar" style="margin-top:.5rem;max-width:300px">
        <div class="progress-fill" style="width:<?= $progress ?>%"></div>
      </div>
    </div>
    <a href="add_task.php" class="btn btn-primary btn-lg">+ New Task</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card" style="--accent-line:var(--accent-cyan)">
      <div class="stat-icon" style="background:rgba(0,212,255,.1)">📋</div>
      <div>
        <div class="stat-label">Total Tasks</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
      </div>
    </div>
    <div class="stat-card" style="--accent-line:var(--accent-amber)">
      <div class="stat-icon" style="background:rgba(245,158,11,.1)">⏳</div>
      <div>
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
      </div>
    </div>
    <div class="stat-card" style="--accent-line:var(--accent-violet)">
      <div class="stat-icon" style="background:rgba(139,92,246,.1)">🔄</div>
      <div>
        <div class="stat-label">In Progress</div>
        <div class="stat-value"><?= $stats['in_progress'] ?></div>
      </div>
    </div>
    <div class="stat-card" style="--accent-line:var(--accent-emerald)">
      <div class="stat-icon" style="background:rgba(16,185,129,.1)">✅</div>
      <div>
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= $stats['completed'] ?></div>
      </div>
    </div>
    <div class="stat-card" style="--accent-line:var(--accent-rose)">
      <div class="stat-icon" style="background:rgba(244,63,94,.1)">🔥</div>
      <div>
        <div class="stat-label">High Priority</div>
        <div class="stat-value"><?= $stats['high'] ?></div>
      </div>
    </div>
    <div class="stat-card" style="--accent-line:var(--accent-rose)">
      <div class="stat-icon" style="background:rgba(244,63,94,.08)">⚠</div>
      <div>
        <div class="stat-label">Overdue</div>
        <div class="stat-value" style="<?= $stats['overdue']>0?'color:var(--accent-rose)':'' ?>"><?= $stats['overdue'] ?></div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem">
    <span style="font-size:.82rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em">Filter:</span>
    <div class="tabs">
      <a href="dashboard.php"                         class="tab <?= !$filter_status?'active':'' ?>">All</a>
      <a href="dashboard.php?status=pending"          class="tab <?= $filter_status==='pending'?'active':'' ?>">Pending</a>
      <a href="dashboard.php?status=in_progress"      class="tab <?= $filter_status==='in_progress'?'active':'' ?>">In Progress</a>
      <a href="dashboard.php?status=completed"        class="tab <?= $filter_status==='completed'?'active':'' ?>">Completed</a>
    </div>
    <div class="tabs">
      <a href="dashboard.php<?= $filter_status?"?status={$filter_status}":'' ?>"          class="tab <?= !$filter_priority?'active':'' ?>">Any Priority</a>
      <a href="?<?= $filter_status?"status={$filter_status}&":'' ?>priority=high"   class="tab <?= $filter_priority==='high'?'active':'' ?>">🔥 High</a>
      <a href="?<?= $filter_status?"status={$filter_status}&":'' ?>priority=medium" class="tab <?= $filter_priority==='medium'?'active':'' ?>">⚡ Medium</a>
      <a href="?<?= $filter_status?"status={$filter_status}&":'' ?>priority=low"    class="tab <?= $filter_priority==='low'?'active':'' ?>">🌱 Low</a>
    </div>
  </div>

  <!-- Task list -->
  <?php if (empty($tasks)): ?>
  <div class="card" style="text-align:center;padding:3rem;border-style:dashed">
    <div style="font-size:3rem;margin-bottom:1rem">📭</div>
    <h3 style="color:var(--text-primary);margin-bottom:.5rem">No tasks found</h3>
    <p style="color:var(--text-muted);margin-bottom:1.5rem">
      <?= $filter_status || $filter_priority ? 'Try clearing the filters.' : 'Create your first task to get started.' ?>
    </p>
    <a href="add_task.php" class="btn btn-primary">+ Create Task</a>
  </div>
  <?php else: ?>
  <div class="tasks-grid">
    <?php foreach ($tasks as $task): ?>
    <?php
      $is_done   = $task['status'] === 'completed';
      $is_overdue= $task['due_date'] && strtotime($task['due_date']) < time() && !$is_done;
      $pclass    = $task['priority'];
    ?>
    <div class="task-card <?= $is_done ? 'completed' : '' ?>" style="animation-delay:<?= array_search($task, $tasks) * 0.04 ?>s">
      <div class="task-priority-bar priority-<?= $pclass ?>"></div>

      <!-- Complete toggle -->
      <form method="POST" action="complete_task.php" style="display:contents">
        <?= csrf_field() ?>
        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
        <button type="submit" class="task-check <?= $is_done ? 'checked' : '' ?>"
                <?= $is_done ? 'disabled' : '' ?>
                title="Mark complete"></button>
      </form>

      <div class="task-body">
        <div class="task-title"><?= esc($task['title']) ?></div>
        <?php if ($task['description']): ?>
          <p style="font-size:.83rem;color:var(--text-secondary);margin:.25rem 0 .5rem;line-height:1.5">
            <?= esc(mb_strimwidth($task['description'], 0, 120, '…')) ?>
          </p>
        <?php endif; ?>
        <div class="task-meta">
          <span class="badge badge-<?= $pclass ?>"><?= strtoupper($pclass) ?></span>
          <span class="badge <?= $is_done?'badge-done':'badge-pending' ?>"><?= status_label($task['status']) ?></span>
          <?php if ($task['due_date']): ?>
          <span style="color:<?= $is_overdue?'var(--accent-rose)':'var(--text-muted)' ?>">
            <?= $is_overdue ? '⚠' : '📅' ?> <?= format_date($task['due_date']) ?>
          </span>
          <?php endif; ?>
          <span>🕐 <?= time_ago($task['created_at']) ?></span>
        </div>
      </div>

      <div class="task-actions">
        <a href="add_task.php?edit=<?= $task['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
        <form method="POST" action="delete_task.php" onsubmit="return confirm('Delete this task?')">
          <?= csrf_field() ?>
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm" title="Delete">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>