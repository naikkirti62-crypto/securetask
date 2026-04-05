<?php
/**
 * SecureTask — One-Time Fix Script
 * Run this ONCE in your browser: http://localhost/securetask/fix.php
 * It will drop and recreate all tables correctly, then delete itself.
 */

$host    = 'localhost';
$port    = '3306';
$db      = 'securetask';
$user    = 'root';
$pass    = '';           // XAMPP default = empty

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('<p style="color:red">DB connect failed: ' . $e->getMessage() . '</p>');
}

$steps = [];

// Disable FK checks so we can drop in any order
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$steps[] = '✅ Foreign key checks disabled';

// Drop old tables
foreach (['sessions_meta','login_attempts','audit_log','tasks','users'] as $t) {
    $pdo->exec("DROP TABLE IF EXISTS `$t`");
    $steps[] = "🗑 Dropped table: $t";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
$steps[] = '✅ Foreign key checks re-enabled';

// ── CREATE users ──
$pdo->exec("
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME     NULL,
    login_count   INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_email    (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$steps[] = '✅ Created table: users (id, username, email, password_hash, role, is_active, created_at, last_login, login_count)';

// ── CREATE tasks ──
$pdo->exec("
CREATE TABLE tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT         NULL,
    priority    ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status      ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    due_date    DATE         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id     (user_id),
    INDEX idx_status      (status),
    INDEX idx_priority    (priority),
    INDEX idx_due_date    (due_date),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$steps[] = '✅ Created table: tasks (id, user_id, title, description, priority, status, due_date, created_at, updated_at)';

// ── CREATE audit_log ──
$pdo->exec("
CREATE TABLE audit_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    event      VARCHAR(80)  NOT NULL,
    detail     TEXT         NULL,
    ip_address VARCHAR(45)  NULL,
    user_agent VARCHAR(300) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_event   (event),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$steps[] = '✅ Created table: audit_log';

// ── CREATE login_attempts ──
$pdo->exec("
CREATE TABLE login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    username     VARCHAR(120) NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip   (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$steps[] = '✅ Created table: login_attempts';

// ── CREATE sessions_meta ──
$pdo->exec("
CREATE TABLE sessions_meta (
    session_id  VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  VARCHAR(300) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$steps[] = '✅ Created table: sessions_meta';

// ── Verify columns ──
$cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$steps[] = '🔍 users columns: ' . implode(', ', $cols);

$cols2 = $pdo->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN);
$steps[] = '🔍 tasks columns: ' . implode(', ', $cols2);

// ── Self-delete ──
// @unlink(__FILE__);
// $steps[] = '🗑 fix.php deleted (security)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SecureTask Fix</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:#0d0d14;font-family:'Space Grotesk',sans-serif;color:#f0f0ff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .box{background:#13131c;border:1px solid rgba(16,185,129,.3);border-radius:16px;padding:2rem;max-width:600px;width:100%}
  h1{font-size:1.3rem;color:#10b981;margin-bottom:1.25rem;display:flex;align-items:center;gap:10px}
  .step{font-family:'JetBrains Mono',monospace;font-size:13px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.05);color:#9090b0;line-height:1.5}
  .step:last-child{border-bottom:none}
  .next{margin-top:1.5rem;background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.25);border-radius:10px;padding:1.1rem 1.25rem}
  .next-title{font-size:.95rem;font-weight:700;color:#00d4ff;margin-bottom:.75rem}
  .next a{display:inline-block;margin-top:.75rem;background:linear-gradient(135deg,#00d4ff,#009acc);color:#000;padding:9px 22px;border-radius:7px;font-weight:700;font-size:.9rem;text-decoration:none}
  .next p{font-size:.85rem;color:#9090b0;line-height:1.7;margin-top:.4rem}
  code{font-family:'JetBrains Mono',monospace;background:#1a1a26;padding:2px 7px;border-radius:4px;font-size:.8rem;color:#00d4ff}
</style>
</head>
<body>
<div class="box">
  <h1>✅ Database Fixed Successfully</h1>
  <div>
    <?php foreach ($steps as $s): ?>
    <div class="step"><?= htmlspecialchars($s) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="next">
    <div class="next-title">All tables recreated correctly. Next steps:</div>
    <p>1. Go to <code>register.php</code> and create your account<br>
       2. Log in and start adding tasks<br>
       3. Delete <code>fix.php</code> from your server after use</p>
    <a href="register.php">→ Go to Register</a>
  </div>
</div>
</body>
</html>