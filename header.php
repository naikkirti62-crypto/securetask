<?php
/**
 * SecureTask — Page Header
 * Include at the top of every page.
 * Requires $page_title to be set before include.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

session_start_secure();
send_security_headers();

if (!validate_session_integrity()) {
    redirect('login.php?reason=session_expired');
}

$current_user = current_user();
$page_title   = isset($page_title) ? esc($page_title) . ' · SecureTask' : 'SecureTask';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="SecureTask — Secure Task Management Platform">
  <meta name="robots" content="noindex, nofollow">
  <title><?= $page_title ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡</text></svg>">
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in() && $current_user): ?>
<header class="site-header">
  <div class="header-inner">

    <a href="dashboard.php" class="logo">
      <div class="logo-icon">🛡</div>
      <span class="logo-text">Secure<span>Task</span></span>
    </a>

    <nav class="header-nav">
      <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'text-cyan':'' ?>">Dashboard</a>
      <a href="add_task.php"  class="nav-link <?= basename($_SERVER['PHP_SELF'])==='add_task.php'?'text-cyan':'' ?>">+ New Task</a>
      <a href="about.php"     class="nav-link <?= basename($_SERVER['PHP_SELF'])==='about.php'?'text-cyan':'' ?>">About</a>
      <a href="contact.php"   class="nav-link <?= basename($_SERVER['PHP_SELF'])==='contact.php'?'text-cyan':'' ?>">Contact</a>
      <span class="nav-badge">🔒 Secured</span>
    </nav>

    <div class="user-menu">
      <div class="user-avatar"><?= strtoupper(substr($current_user['username'], 0, 2)) ?></div>
      <div class="user-info">
        <span class="user-name"><?= esc($current_user['username']) ?></span>
        <span class="user-role"><?= esc($current_user['role']) ?></span>
      </div>
      <a href="logout.php" class="btn btn-ghost btn-sm" title="Logout">⏻</a>
    </div>

  </div>
</header>
<?php endif; ?>