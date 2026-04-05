<?php
$page_title = 'Sign In';
require_once __DIR__ . '/header.php';

if (is_logged_in()) redirect('dashboard.php');

$error   = '';
$success = '';
$reason  = get('reason');
if ($reason === 'session_expired') $error = 'Your session expired. Please sign in again.';
if ($reason === 'registered')      $success = '✅ Account created! Sign in below.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    guard_inputs($_POST);

    $credential = post('credential');
    $password   = post('password');

    if (empty($credential) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = login_user($credential, $password);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            redirect('dashboard.php');
        }
    }
}

$ip         = get_client_ip();
$attempts   = get_failed_login_count($ip);
$remaining  = remaining_lockout($ip);
?>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon">🛡</div>
      <div>
        <div class="auth-title">Welcome back</div>
        <div class="auth-subtitle">Sign in to your SecureTask account</div>
      </div>
    </div>

    <?php if ($error):   ?><div class="alert alert-error">⚠ <?= esc($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>

    <?php if ($attempts >= 3 && $attempts < MAX_ATTEMPTS): ?>
    <div class="alert alert-info">
      ⚠ <?= MAX_ATTEMPTS - $attempts ?> attempt(s) remaining before your IP is locked for 15 minutes.
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="credential">Email or Username</label>
        <input type="text" id="credential" name="credential"
               value="<?= esc(post('credential')) ?>"
               placeholder="you@example.com or username"
               autocomplete="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;margin-top:.5rem">
        🔑 Sign In
      </button>
    </form>

    <div class="divider"><span class="divider-text">or</span></div>

    <div style="text-align:center;font-size:0.88rem;color:var(--text-secondary)">
      Don't have an account?
      <a href="register.php" style="color:var(--accent-cyan);text-decoration:none;font-weight:600"> Create one →</a>
    </div>

    <!-- Security indicator -->
    <div style="margin-top:1.5rem;padding:0.85rem;background:var(--bg-secondary);border-radius:var(--radius-sm);display:flex;gap:.5rem;align-items:center;font-size:.78rem;color:var(--text-muted);font-family:var(--font-mono)">
      <span style="color:var(--accent-emerald)">🔒</span>
      Connection secured · Argon2id hashing · CSRF protected
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>