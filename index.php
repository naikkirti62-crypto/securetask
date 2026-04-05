<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

session_start_secure();
send_security_headers();

if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SecureTask — Enterprise Task Management</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡</text></svg>">
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <style>
    .hero {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 4rem 2rem;
      position: relative;
      z-index: 1;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,212,255,0.08);
      border: 1px solid rgba(0,212,255,0.25);
      color: var(--accent-cyan);
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-family: var(--font-mono);
      font-weight: 600;
      margin-bottom: 2rem;
      letter-spacing: 0.06em;
      animation: fadeSlideIn 0.8s ease both;
    }
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(3rem, 7vw, 6rem);
      font-weight: 800;
      line-height: 1.05;
      margin-bottom: 1.5rem;
      animation: fadeSlideIn 0.8s 0.15s ease both;
    }
    .hero-title .line1 {
      display: block;
      background: linear-gradient(135deg, var(--text-primary) 50%, var(--accent-cyan));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero-title .line2 {
      display: block;
      background: linear-gradient(135deg, var(--accent-violet), var(--accent-cyan));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero-subtitle {
      font-size: clamp(1rem, 2vw, 1.2rem);
      color: var(--text-secondary);
      max-width: 560px;
      margin: 0 auto 2.5rem;
      line-height: 1.7;
      animation: fadeSlideIn 0.8s 0.3s ease both;
    }
    .hero-cta {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      justify-content: center;
      animation: fadeSlideIn 0.8s 0.45s ease both;
    }
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.25rem;
      max-width: 960px;
      width: 100%;
      margin: 4rem auto 0;
      animation: fadeSlideIn 0.8s 0.6s ease both;
    }
    .feature-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      text-align: left;
      transition: all 0.25s;
    }
    .feature-card:hover {
      border-color: var(--border-accent);
      transform: translateY(-4px);
      box-shadow: var(--shadow-glow);
    }
    .feature-emoji { font-size: 2rem; margin-bottom: 0.75rem; display: block; }
    .feature-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-primary); }
    .feature-desc  { font-size: 0.83rem; color: var(--text-secondary); line-height: 1.6; }
    .tech-stack {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 3rem;
      animation: fadeSlideIn 0.8s 0.75s ease both;
    }
    .tech-pill {
      background: var(--bg-card);
      border: 1px solid var(--border);
      color: var(--text-muted);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-family: var(--font-mono);
    }
    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<main class="hero">
  <span class="hero-badge">
    <span style="width:8px;height:8px;border-radius:50%;background:var(--accent-emerald);animation:pulse 2s infinite"></span>
    Cloud-Ready · 5,000+ API Hits/min · Production Grade
  </span>

  <h1 class="hero-title">
    <span class="line1">Manage Tasks.</span>
    <span class="line2">Stay Secure.</span>
  </h1>

  <p class="hero-subtitle">
    Enterprise-grade task management with a custom security layer —
    CSRF protection, Argon2id hashing, rate limiting, CSP headers,
    session integrity validation & SQL injection prevention. Built for scale.
  </p>

  <div class="hero-cta">
    <a href="register.php" class="btn btn-primary btn-lg">🚀 Get Started Free</a>
    <a href="login.php"    class="btn btn-ghost btn-lg">Sign In →</a>
    <a href="about.php"    class="btn btn-ghost btn-lg">Architecture Docs</a>
  </div>

  <div class="features">
    <div class="feature-card">
      <span class="feature-emoji">🔐</span>
      <div class="feature-title">Custom Security Layer</div>
      <p class="feature-desc">Hand-crafted security: CSRF tokens, Argon2id passwords, session hijack detection, rate limiting, and CSP headers.</p>
    </div>
    <div class="feature-card">
      <span class="feature-emoji">⚡</span>
      <div class="feature-title">High Performance</div>
      <p class="feature-desc">Optimised PDO queries with prepared statements. Supports 5,000+ concurrent API requests with horizontal cloud scaling.</p>
    </div>
    <div class="feature-card">
      <span class="feature-emoji">🗄️</span>
      <div class="feature-title">SQL Database</div>
      <p class="feature-desc">MySQL/MariaDB with normalised schema, indexed queries, foreign-key constraints and full audit logging.</p>
    </div>
    <div class="feature-card">
      <span class="feature-emoji">📊</span>
      <div class="feature-title">Full Audit Trail</div>
      <p class="feature-desc">Every action is logged — logins, task changes, CSRF violations, SQL-injection probes, and more.</p>
    </div>
  </div>

  <div class="tech-stack">
    <span class="tech-pill">PHP 8.2+</span>
    <span class="tech-pill">MySQL 8</span>
    <span class="tech-pill">Argon2id</span>
    <span class="tech-pill">PDO + Prepared Stmts</span>
    <span class="tech-pill">CSRF Tokens</span>
    <span class="tech-pill">CSP Headers</span>
    <span class="tech-pill">AWS / GCP Ready</span>
    <span class="tech-pill">Docker</span>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>