<?php
/**
 * SecureTask — Core Functions
 * Utility helpers shared across all modules
 */
 
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';   // pulled in via header
 
/* ══════════════════════════════════════════
   SESSION MANAGEMENT
══════════════════════════════════════════ */
 
function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,          // set true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('SECURETASK_SID');
        session_start();
    }
}
 
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
 
function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}
 
function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $user = db_row('SELECT id, username, email, role, created_at, last_login, login_count
                         FROM users WHERE id = ? AND is_active = 1', [$_SESSION['user_id']]);
    }
    return $user;
}
 
/* ══════════════════════════════════════════
   NAVIGATION
══════════════════════════════════════════ */
 
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
 
/* ══════════════════════════════════════════
   INPUT SANITIZATION
══════════════════════════════════════════ */
 
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
 
function post(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}
 
function get(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}
 
function post_int(string $key, int $default = 0): int {
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
}
 
function get_int(string $key, int $default = 0): int {
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}
 
/* ══════════════════════════════════════════
   TASK FUNCTIONS
══════════════════════════════════════════ */
 
function get_tasks(int $user_id, string $status = '', string $priority = ''): array {
    $sql    = 'SELECT * FROM tasks WHERE user_id = ?';
    $params = [$user_id];
    if ($status)   { $sql .= ' AND status = ?';   $params[] = $status;   }
    if ($priority) { $sql .= ' AND priority = ?'; $params[] = $priority; }
    $sql .= ' ORDER BY FIELD(priority,"high","medium","low"), due_date ASC, created_at DESC';
    return db_query($sql, $params);
}
 
function get_task(int $id, int $user_id): ?array {
    return db_row('SELECT * FROM tasks WHERE id = ? AND user_id = ?', [$id, $user_id]);
}
 
function create_task(int $user_id, string $title, string $desc,
                     string $priority, string $due_date): int {
    db_exec(
        'INSERT INTO tasks (user_id, title, description, priority, due_date)
         VALUES (?, ?, ?, ?, ?)',
        [$user_id, $title, $desc ?: null, $priority, $due_date ?: null]
    );
    $id = db_last_id();
    audit('task_created', "Task #{$id}: {$title}");
    return $id;
}
 
function update_task(int $id, int $user_id, string $title, string $desc,
                     string $priority, string $status, string $due_date): bool {
    $rows = db_exec(
        'UPDATE tasks SET title=?, description=?, priority=?, status=?, due_date=?
         WHERE id=? AND user_id=?',
        [$title, $desc ?: null, $priority, $status, $due_date ?: null, $id, $user_id]
    );
    if ($rows) audit('task_updated', "Task #{$id}: {$title}");
    return (bool) $rows;
}
 
function complete_task(int $id, int $user_id): bool {
    $rows = db_exec(
        "UPDATE tasks SET status='completed' WHERE id=? AND user_id=?",
        [$id, $user_id]
    );
    if ($rows) audit('task_completed', "Task #{$id} marked complete");
    return (bool) $rows;
}
 
function delete_task(int $id, int $user_id): bool {
    $task = get_task($id, $user_id);
    $rows = db_exec('DELETE FROM tasks WHERE id=? AND user_id=?', [$id, $user_id]);
    if ($rows && $task) audit('task_deleted', "Task #{$id}: {$task['title']}");
    return (bool) $rows;
}
 
function task_stats(int $user_id): array {
    $rows = db_query(
        "SELECT status, priority, COUNT(*) AS cnt FROM tasks
         WHERE user_id = ? GROUP BY status, priority",
        [$user_id]
    );
    $stats = [
        'total'       => 0,
        'pending'     => 0,
        'in_progress' => 0,
        'completed'   => 0,
        'high'        => 0,
        'overdue'     => 0,
    ];
    foreach ($rows as $r) {
        $stats['total']         += $r['cnt'];
        $stats[$r['status']]    += $r['cnt'];
        if ($r['priority'] === 'high') $stats['high'] += $r['cnt'];
    }
    // overdue: due_date < today AND not completed
    $over = db_row(
        "SELECT COUNT(*) AS c FROM tasks
         WHERE user_id=? AND due_date < CURDATE() AND status != 'completed'",
        [$user_id]
    );
    $stats['overdue'] = (int)($over['c'] ?? 0);
    return $stats;
}
 
/* ══════════════════════════════════════════
   AUDIT LOG
══════════════════════════════════════════ */
 
function audit(string $event, string $detail = ''): void {
    $uid = $_SESSION['user_id'] ?? null;
    $ip  = get_client_ip();
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    db_exec(
        'INSERT INTO audit_log (user_id, event, detail, ip_address, user_agent) VALUES (?,?,?,?,?)',
        [$uid, $event, $detail, $ip, substr($ua, 0, 300)]
    );
}
 
function get_audit_log(int $user_id, int $limit = 50): array {
    return db_query(
        'SELECT * FROM audit_log WHERE user_id=? ORDER BY created_at DESC LIMIT ?',
        [$user_id, $limit]
    );
}
 
/* ══════════════════════════════════════════
   USER FUNCTIONS
══════════════════════════════════════════ */
 
function register_user(string $username, string $email, string $password): array {
    // Validation
    if (strlen($username) < 3 || strlen($username) > 60)
        return ['error' => 'Username must be 3–60 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['error' => 'Invalid email address.'];
    if (strlen($password) < 8)
        return ['error' => 'Password must be at least 8 characters.'];
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
        return ['error' => 'Password must contain an uppercase letter and a number.'];
 
    // Duplicate check
    $exists = db_row('SELECT id FROM users WHERE email=? OR username=?', [$email, $username]);
    if ($exists) return ['error' => 'Email or username already in use.'];
 
    $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]);
    db_exec(
        'INSERT INTO users (username, email, password_hash) VALUES (?,?,?)',
        [$username, $email, $hash]
    );
    $id = db_last_id();
    audit('user_registered', "New user: {$username}");
    return ['success' => true, 'id' => $id];
}
 
function login_user(string $credential, string $password): array {
    $ip = get_client_ip();
 
    // Rate limit check
    if (is_rate_limited($ip)) {
        audit('rate_limit_blocked', "IP: {$ip}");
        return ['error' => 'Too many login attempts. Please wait 15 minutes.'];
    }
 
    // Accept email or username
    $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $user  = db_row("SELECT * FROM users WHERE {$field}=? AND is_active=1", [$credential]);
 
    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($ip, $credential);
        audit('login_failed', "Credential: {$credential}");
        return ['error' => 'Invalid username/email or password.'];
    }
 
    // Successful login — regenerate session
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['login_time']= time();
 
    db_exec('UPDATE users SET last_login=NOW(), login_count=login_count+1 WHERE id=?', [$user['id']]);
    audit('login_success', "User: {$user['username']}");
    return ['success' => true, 'user' => $user];
}
 
function logout_user(): void {
    audit('logout', 'User logged out');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
 
/* ══════════════════════════════════════════
   CSRF TOKEN
══════════════════════════════════════════ */
 
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
 
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
 
function verify_csrf(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        audit('csrf_violation', 'CSRF token mismatch');
        http_response_code(403);
        die('<p style="color:red">Security validation failed. Please go back and try again.</p>');
    }
}
 
/* ══════════════════════════════════════════
   MISC HELPERS
══════════════════════════════════════════ */
 
function get_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}
 
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60)   . 'm ago';
    if ($diff < 86400)  return floor($diff/3600)  . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
 
function format_date(?string $date): string {
    return $date ? date('M j, Y', strtotime($date)) : '—';
}
 
function priority_class(string $p): string {
    return match($p) { 'high'=>'rose','medium'=>'amber','low'=>'emerald', default=>'muted' };
}
 
function status_label(string $s): string {
    return match($s) {
        'pending'    => '⏳ Pending',
        'in_progress'=> '🔄 In Progress',
        'completed'  => '✅ Completed',
        default      => ucfirst($s)
    };
}
 