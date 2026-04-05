<?php
$page_title = 'Contact & Support';
require_once __DIR__ . '/header.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    guard_inputs($_POST);

    $name    = post('name');
    $email   = post('email');
    $subject = post('subject');
    $message = post('message');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($message) < 20) {
        $error = 'Message must be at least 20 characters.';
    } else {
        // In production: send email via SMTP (PHPMailer / SES)
        // mail($to, $subject, $message, $headers);
        audit('contact_form', "From: {$name} <{$email}> — {$subject}");
        $success = true;
    }
}
?>

<main class="page-wrapper">
  <div class="page-header">
    <div>
      <h1 class="page-title">Contact & Support</h1>
      <p class="page-subtitle">Get help, report a bug, or share feedback</p>
    </div>
    <?php if (is_logged_in()): ?>
    <a href="dashboard.php" class="btn btn-ghost">← Dashboard</a>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

    <!-- Form -->
    <div class="card">
      <?php if ($success): ?>
      <div class="alert alert-success">
        ✅ Thank you! Your message has been received. We'll respond within 24 hours.
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-error">⚠ <?= esc($error) ?></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" action="contact.php">
        <?= csrf_field() ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group">
            <label>Your Name <span style="color:var(--accent-rose)">*</span></label>
            <input type="text" name="name" value="<?= esc(post('name')) ?>" placeholder="John Smith" required>
          </div>
          <div class="form-group">
            <label>Email Address <span style="color:var(--accent-rose)">*</span></label>
            <input type="email" name="email" value="<?= esc(post('email')) ?>" placeholder="you@example.com" required>
          </div>
        </div>

        <div class="form-group">
          <label>Subject</label>
          <select name="subject">
            <option value="support">Technical Support</option>
            <option value="bug">Bug Report</option>
            <option value="feature">Feature Request</option>
            <option value="security">Security Issue</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label>Message <span style="color:var(--accent-rose)">*</span></label>
          <textarea name="message" rows="6" placeholder="Describe your issue or feedback in detail…" required><?= esc(post('message')) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">📨 Send Message</button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Info sidebar -->
    <div style="display:flex;flex-direction:column;gap:1rem">
      <div class="card">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.75rem;color:var(--text-primary)">📬 Contact Info</h3>
        <div style="display:flex;flex-direction:column;gap:.75rem;font-size:.85rem;color:var(--text-secondary)">
          <div>📧 support@securetask.io</div>
          <div>🐛 github.com/securetask/issues</div>
          <div>📖 docs.securetask.io</div>
          <div>⏱ Response within 24h</div>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.75rem;color:var(--text-primary)">🔐 Security Disclosure</h3>
        <p style="font-size:.82rem;color:var(--text-muted);line-height:1.7">
          Found a security vulnerability? Please report it privately via
          <strong style="color:var(--accent-cyan)">security@securetask.io</strong> before public disclosure.
          We follow responsible disclosure practices.
        </p>
      </div>

      <div class="card">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.75rem;color:var(--text-primary)">⚡ System Status</h3>
        <div style="display:flex;flex-direction:column;gap:.5rem">
          <?php
          $services = ['API Endpoints','Database','Auth Service','File Storage'];
          foreach ($services as $s): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;font-size:.82rem">
            <span style="color:var(--text-secondary)"><?= $s ?></span>
            <span style="display:flex;align-items:center;gap:5px;color:var(--accent-emerald);font-family:var(--font-mono);font-size:.75rem">
              <span class="status-dot"></span> Operational
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>