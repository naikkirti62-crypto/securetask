<?php
$page_title = 'Architecture & Docs';
require_once __DIR__ . '/header.php';
?>

<main class="page-wrapper">
  <div class="page-header">
    <div>
      <h1 class="page-title">Architecture & Documentation</h1>
      <p class="page-subtitle">Complete technical documentation for the SecureTask platform</p>
    </div>
    <?php if (is_logged_in()): ?>
    <a href="dashboard.php" class="btn btn-ghost">← Dashboard</a>
    <?php endif; ?>
  </div>

  <!-- Architecture Overview -->
  <div class="card" style="margin-bottom:1.5rem;border-left:3px solid var(--accent-cyan)">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:1rem;color:var(--text-primary)">🏗 System Architecture</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
      <?php
      $layers = [
        ['☁', 'Cloud Hosting', 'AWS EC2 / GCP Compute, Load Balancer, Auto Scaling Group', 'var(--accent-cyan)'],
        ['🌐', 'Frontend', 'Pure PHP templating, CSS3, Vanilla JS — no framework overhead', 'var(--accent-violet)'],
        ['⚙', 'Backend', 'PHP 8.2+ with PDO, custom MVC pattern, RESTful action handlers', 'var(--accent-emerald)'],
        ['🔐', 'Security Layer', 'Custom-built: CSRF, rate limiting, session validation, CSP, XSS', 'var(--accent-amber)'],
        ['🗄', 'Database', 'MySQL 8 / MariaDB with indexed schema, foreign keys, audit log', 'var(--accent-rose)'],
        ['📊', 'Monitoring', 'PHP error_log → CloudWatch / Stackdriver, audit_log table', 'var(--accent-cyan)'],
      ];
      foreach ($layers as [$icon, $title, $desc, $color]): ?>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border:1px solid var(--border)">
        <div style="font-size:1.5rem;margin-bottom:.5rem"><?= $icon ?></div>
        <div style="font-weight:700;color:<?= $color ?>;font-size:.9rem;margin-bottom:.4rem"><?= $title ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);line-height:1.6"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Database Schema -->
  <div class="card" style="margin-bottom:1.5rem">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:1rem;color:var(--text-primary)">🗄 Database Schema (MySQL)</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
      <?php
      $tables = [
        ['users',          ['id INT PK','username VARCHAR(60) UNIQUE','email VARCHAR(120) UNIQUE','password_hash VARCHAR(255)','role ENUM(admin,user)','is_active TINYINT','created_at DATETIME','last_login DATETIME','login_count INT']],
        ['tasks',          ['id INT PK','user_id INT FK→users','title VARCHAR(200)','description TEXT','priority ENUM(low,medium,high)','status ENUM(pending,in_progress,completed)','due_date DATE','created_at DATETIME','updated_at DATETIME']],
        ['audit_log',      ['id INT PK','user_id INT FK→users','event VARCHAR(80)','detail TEXT','ip_address VARCHAR(45)','user_agent VARCHAR(300)','created_at DATETIME']],
        ['login_attempts', ['id INT PK','ip_address VARCHAR(45)','username VARCHAR(120)','attempted_at DATETIME']],
        ['sessions_meta',  ['session_id VARCHAR(128) PK','user_id INT FK→users','ip_address VARCHAR(45)','user_agent VARCHAR(300)','created_at DATETIME','last_active DATETIME']],
      ];
      foreach ($tables as [$name, $cols]): ?>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);border:1px solid var(--border);overflow:hidden">
        <div style="padding:.7rem 1rem;background:rgba(0,212,255,.06);border-bottom:1px solid var(--border);font-family:var(--font-mono);font-size:.85rem;font-weight:700;color:var(--accent-cyan)">
          📄 <?= $name ?>
        </div>
        <div style="padding:.75rem">
          <?php foreach ($cols as $col): ?>
          <div style="font-family:var(--font-mono);font-size:.73rem;color:var(--text-muted);padding:2px 0;border-bottom:1px solid rgba(255,255,255,.03)">
            <?= $col ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Security Layer -->
  <div class="card" style="margin-bottom:1.5rem">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:1rem;color:var(--text-primary)">🔐 Custom Security Layer</h2>
    <div class="security-grid">
      <?php
      $features = [
        ['🛡','CSRF Protection','Every POST form includes a cryptographically random token bound to the session. Token is validated via hash_equals() to prevent timing attacks.','Active on all forms'],
        ['🔑','Argon2id Hashing','Passwords hashed with PHP PASSWORD_ARGON2ID — memory: 64MB, iterations: 4, threads: 2. Rehash detection built in.','memory=64MB, t=4'],
        ['⏱','Rate Limiting','Sliding-window rate limit: max 10 login attempts per IP per 15 minutes, stored in DB. Automatic cleanup of stale records.','10 req / 15 min'],
        ['🔍','Session Integrity','Each session stores IP /24 subnet and User-Agent. Mismatch triggers immediate logout. 30-minute idle timeout enforced.','IP + UA fingerprint'],
        ['📋','Content Security Policy','Full CSP header restricts script/style/font/connect sources. X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Referrer-Policy.','Level 2 CSP'],
        ['🧹','XSS Prevention','All output is passed through htmlspecialchars() with ENT_QUOTES|ENT_HTML5. Input stripped via strip_tags where applicable.','Output-escaped'],
        ['💉','SQLi Prevention','Primary: PDO prepared statements for ALL queries. Secondary: regex pattern matching detects UNION/DROP/EXEC probes and logs them.','Dual-layer'],
        ['📝','Full Audit Log','Every significant action stored: logins, logouts, CSRF violations, SQLi probes, task CRUD. Queryable by user and event type.','Tamper-evident'],
      ];
      foreach ($features as [$icon, $title, $desc, $status]): ?>
      <div class="security-card">
        <div class="security-card-header">
          <div class="security-icon" style="background:rgba(0,212,255,.08);color:var(--accent-cyan)"><?= $icon ?></div>
          <div class="security-card-title"><?= $title ?></div>
        </div>
        <p class="security-card-desc"><?= $desc ?></p>
        <div class="security-status">
          <div class="status-dot"></div>
          <span style="color:var(--accent-emerald)"><?= $status ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cloud Architecture & Scalability -->
  <div class="card" style="margin-bottom:1.5rem">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:1rem;color:var(--text-primary)">☁ Cloud Deployment & Scalability (5,000+ API hits)</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
      <?php
      $cloud = [
        ['🔀','Load Balancer','AWS ALB / GCP Load Balancer distributes traffic across multiple PHP-FPM instances. Health checks auto-remove unhealthy nodes.'],
        ['📦','Auto Scaling','EC2 Auto Scaling Group scales PHP instances 2–20 based on CPU > 60%. New instances bootstrap via AMI or Docker container.'],
        ['🗄','RDS MySQL','Amazon RDS MySQL 8 with Multi-AZ, read replicas for SELECT queries. Automated backups, point-in-time recovery, 99.95% SLA.'],
        ['🔄','Redis Cache','ElastiCache Redis for session storage (replaces file sessions at scale) and query result caching. TTL 300s for stat queries.'],
        ['🛡','WAF','AWS WAF rules block known attack signatures, geographic restrictions, and bot traffic before it reaches application servers.'],
        ['🌐','CDN','CloudFront / Cloud CDN serves static assets (style.css, fonts) from edge locations, reducing origin load significantly.'],
      ];
      foreach ($cloud as [$icon, $title, $desc]): ?>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border:1px solid var(--border)">
        <div style="font-size:1.4rem;margin-bottom:.5rem"><?= $icon ?></div>
        <div style="font-weight:700;color:var(--text-primary);font-size:.9rem;margin-bottom:.4rem"><?= $title ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);line-height:1.6"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- File Structure -->
  <div class="card">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:1rem;color:var(--text-primary)">📁 Project File Structure</h2>
    <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1.25rem;font-family:var(--font-mono);font-size:.8rem;color:var(--text-secondary);line-height:2;border:1px solid var(--border)">
      <span style="color:var(--accent-cyan)">securetask/</span><br>
      ├── <span style="color:var(--accent-emerald)">index.php</span>         <span style="color:var(--text-muted)"># Landing / entry point</span><br>
      ├── <span style="color:var(--accent-emerald)">db.php</span>            <span style="color:var(--text-muted)"># PDO connection, schema installer, DB helpers</span><br>
      ├── <span style="color:var(--accent-rose)">security.php</span>      <span style="color:var(--text-muted)"># 🔐 CUSTOM SECURITY LAYER (CSRF, rate limit, XSS, SQLi)</span><br>
      ├── <span style="color:var(--accent-emerald)">functions.php</span>    <span style="color:var(--text-muted)"># Business logic: tasks, users, audit, session helpers</span><br>
      ├── <span style="color:var(--accent-cyan)">header.php</span>        <span style="color:var(--text-muted)"># Shared HTML head + navigation</span><br>
      ├── <span style="color:var(--accent-cyan)">footer.php</span>        <span style="color:var(--text-muted)"># Shared HTML footer</span><br>
      ├── <span style="color:var(--accent-cyan)">style.css</span>         <span style="color:var(--text-muted)"># Full design system (Space Grotesk, dark theme)</span><br>
      ├── <span style="color:var(--text-primary)">login.php</span>         <span style="color:var(--text-muted)"># Sign in page</span><br>
      ├── <span style="color:var(--text-primary)">register.php</span>      <span style="color:var(--text-muted)"># Sign up page</span><br>
      ├── <span style="color:var(--text-primary)">logout.php</span>        <span style="color:var(--text-muted)"># Session destruction</span><br>
      ├── <span style="color:var(--text-primary)">dashboard.php</span>     <span style="color:var(--text-muted)"># Main task management view</span><br>
      ├── <span style="color:var(--text-primary)">add_task.php</span>      <span style="color:var(--text-muted)"># Create + edit tasks</span><br>
      ├── <span style="color:var(--text-primary)">complete_task.php</span> <span style="color:var(--text-muted)"># POST action: mark complete</span><br>
      ├── <span style="color:var(--text-primary)">delete_task.php</span>   <span style="color:var(--text-muted)"># POST action: delete task</span><br>
      ├── <span style="color:var(--text-primary)">about.php</span>         <span style="color:var(--text-muted)"># Architecture documentation</span><br>
      └── <span style="color:var(--text-primary)">contact.php</span>       <span style="color:var(--text-muted)"># Contact / support form</span>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>