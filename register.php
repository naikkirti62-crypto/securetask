<?php
$page_title = 'Create Account';
require_once __DIR__ . '/header.php';

if (is_logged_in()) redirect('dashboard.php');

$error  = '';
$fields = ['username'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    guard_inputs($_POST);

    $username  = post('username');
    $email     = post('email');
    $password  = post('password');
    $confirm   = post('confirm');
    $fields    = ['username'=>$username,'email'=>$email];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $weaknesses = validate_password_strength($password);
        if ($weaknesses) {
            $error = 'Password needs: ' . implode(', ', $weaknesses) . '.';
        } else {
            $result = register_user($username, $email, $password);
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                redirect('login.php?reason=registered');
            }
        }
    }
}
?>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon">🛡</div>
      <div>
        <div class="auth-title">Create Account</div>
        <div class="auth-subtitle">Join SecureTask — free forever</div>
      </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error">⚠ <?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="register.php" autocomplete="on">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= esc($fields['username']) ?>"
               placeholder="cooldev42"
               pattern="[a-zA-Z0-9_\-]{3,60}"
               title="3-60 chars: letters, numbers, _ or -"
               autocomplete="username" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= esc($fields['email']) ?>"
               placeholder="you@example.com"
               autocomplete="email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Min 8 chars, 1 uppercase, 1 number"
               autocomplete="new-password" required
               oninput="checkStrength(this.value)">
        <div id="strength-bar" class="progress-bar" style="margin-top:.5rem">
          <div class="progress-fill" id="strength-fill" style="width:0%;transition:width .4s,background .4s"></div>
        </div>
        <span id="strength-label" style="font-size:.75rem;color:var(--text-muted);font-family:var(--font-mono)"></span>
      </div>

      <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm"
               placeholder="••••••••"
               autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;margin-top:.5rem">
        🚀 Create Account
      </button>
    </form>

    <div class="divider"><span class="divider-text">or</span></div>

    <div style="text-align:center;font-size:0.88rem;color:var(--text-secondary)">
      Already have an account?
      <a href="login.php" style="color:var(--accent-cyan);text-decoration:none;font-weight:600"> Sign in →</a>
    </div>

    <div style="margin-top:1.5rem;padding:.85rem;background:var(--bg-secondary);border-radius:var(--radius-sm);font-size:.75rem;color:var(--text-muted);font-family:var(--font-mono);line-height:1.7">
      🔒 Password hashed with <strong>Argon2id</strong><br>
      🛡 All inputs validated & sanitised<br>
      📋 Registration audit logged
    </div>

  </div>
</div>

<script>
function checkStrength(pw) {
  let score = 0;
  if (pw.length >= 8)                  score++;
  if (/[A-Z]/.test(pw))               score++;
  if (/[0-9]/.test(pw))               score++;
  if (/[^a-zA-Z0-9]/.test(pw))        score++;
  if (pw.length >= 12)                 score++;

  const pct    = (score / 5) * 100;
  const colors = ['#f43f5e','#f43f5e','#f59e0b','#10b981','#00d4ff'];
  const labels = ['','Very Weak','Weak','Good','Strong','Very Strong'];

  document.getElementById('strength-fill').style.width      = pct + '%';
  document.getElementById('strength-fill').style.background = colors[score - 1] || '#333';
  document.getElementById('strength-label').textContent     = labels[score] || '';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>