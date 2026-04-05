<?php
/**
 * SecureTask — Custom Security Layer
 * ────────────────────────────────────
 * Implements: Rate limiting, IP blocking, XSS protection,
 * SQL-injection prevention hooks, CSP headers, brute-force
 * protection, and input validation utilities.
 */

require_once __DIR__ . '/db.php';

/* ══════════════════════════════════════════
   HTTP SECURITY HEADERS
   Sent on every page load via header.php
══════════════════════════════════════════ */

function send_security_headers(): void {
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none';");

    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // Clickjacking protection
    header('X-Frame-Options: DENY');

    // XSS filter (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy (disable unused browser features)
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // HSTS (enable in production with HTTPS)
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

    // Remove server fingerprint
    header_remove('X-Powered-By');
    header_remove('Server');
}

/* ══════════════════════════════════════════
   RATE LIMITING  (DB-backed sliding window)
   Window: 15 min | Max attempts: 10
══════════════════════════════════════════ */

define('RATE_WINDOW_SECONDS', 900);   // 15 minutes
define('MAX_ATTEMPTS',        10);    // per IP per window

function is_rate_limited(string $ip): bool {
    $count = db_row(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$ip, RATE_WINDOW_SECONDS]
    );
    return ((int)($count['c'] ?? 0)) >= MAX_ATTEMPTS;
}

function record_login_attempt(string $ip, string $username = ''): void {
    db_exec(
        'INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)',
        [$ip, $username]
    );
    // Purge old records (keep table small)
    db_exec(
        "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [RATE_WINDOW_SECONDS * 2]
    );
}

function remaining_lockout(string $ip): int {
    $oldest = db_row(
        "SELECT MIN(attempted_at) AS t FROM login_attempts
         WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$ip, RATE_WINDOW_SECONDS]
    );
    if (!$oldest['t']) return 0;
    $unlock = strtotime($oldest['t']) + RATE_WINDOW_SECONDS;
    return max(0, $unlock - time());
}

/* ══════════════════════════════════════════
   XSS PROTECTION
══════════════════════════════════════════ */

/**
 * Sanitise output — always use before echoing user data
 */
function esc(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitise an entire array recursively
 */
function esc_array(array $arr): array {
    return array_map(fn($v) => is_array($v) ? esc_array($v) : esc((string)$v), $arr);
}

/**
 * Strip dangerous HTML from rich-text input (no DOM extension required)
 */
function sanitise_html(string $html): string {
    // Allow only safe inline elements
    $allowed = '<b><strong><em><i><u><br><p><ul><ol><li><span>';
    return strip_tags($html, $allowed);
}

/* ══════════════════════════════════════════
   INPUT VALIDATION
══════════════════════════════════════════ */

function validate_username(string $v): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_\-]{3,60}$/', $v);
}

function validate_email(string $v): bool {
    return (bool) filter_var($v, FILTER_VALIDATE_EMAIL);
}

function validate_password_strength(string $p): array {
    $errors = [];
    if (strlen($p) < 8)                        $errors[] = 'At least 8 characters';
    if (!preg_match('/[A-Z]/', $p))            $errors[] = 'One uppercase letter';
    if (!preg_match('/[a-z]/', $p))            $errors[] = 'One lowercase letter';
    if (!preg_match('/[0-9]/', $p))            $errors[] = 'One digit';
    if (!preg_match('/[^a-zA-Z0-9]/', $p))     $errors[] = 'One special character';
    return $errors;
}

function validate_enum(string $val, array $allowed): bool {
    return in_array($val, $allowed, true);
}

function validate_date(string $d): bool {
    if (empty($d)) return true; // optional dates OK
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/* ══════════════════════════════════════════
   SESSION FIXATION PREVENTION
══════════════════════════════════════════ */

function validate_session_integrity(): bool {
    if (!is_logged_in()) return true;

    $ip = get_client_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Check for session hijacking: IP or UA changed mid-session
    if (isset($_SESSION['_ip']) && $_SESSION['_ip'] !== $ip) {
        // Allow same /24 subnet (handles mobile networks slightly)
        $saved  = explode('.', $_SESSION['_ip']);
        $current= explode('.', $ip);
        if ($saved[0]!=$current[0] || $saved[1]!=$current[1] || $saved[2]!=$current[2]) {
            session_destroy();
            return false;
        }
    }

    if (isset($_SESSION['_ua']) && $_SESSION['_ua'] !== $ua) {
        session_destroy();
        return false;
    }

    // Store on first request after login
    if (!isset($_SESSION['_ip'])) {
        $_SESSION['_ip'] = $ip;
        $_SESSION['_ua'] = $ua;
    }

    // Idle timeout: 30 minutes
    if (isset($_SESSION['_last_active']) && (time() - $_SESSION['_last_active']) > 1800) {
        session_destroy();
        return false;
    }
    $_SESSION['_last_active'] = time();

    return true;
}

/* ══════════════════════════════════════════
   SQL INJECTION GUARD (belt-and-suspenders)
   Primary protection is PDO prepared statements.
   This secondary layer detects suspicious patterns.
══════════════════════════════════════════ */

define('SQLI_PATTERNS', [
    '/(\bUNION\b.*\bSELECT\b)/i',
    '/(\bDROP\b.*\bTABLE\b)/i',
    '/(\bINSERT\b.*\bINTO\b)/i',
    '/(\bDELETE\b.*\bFROM\b)/i',
    '/(\bEXEC\b|\bEXECUTE\b)/i',
    '/(\bSCRIPT\b)/i',
    "/(--|#|\/\*)/",
    '/(\bOR\b\s+\d+=\d+)/i',
    "/('.*'--)/",
]);

function detect_sqli(string $input): bool {
    foreach (SQLI_PATTERNS as $pattern) {
        if (preg_match($pattern, $input)) return true;
    }
    return false;
}

function guard_inputs(array $inputs): void {
    foreach ($inputs as $key => $val) {
        if (is_string($val) && detect_sqli($val)) {
            $ip = get_client_ip();
            error_log("SQLi probe detected — IP:{$ip} KEY:{$key} VAL:{$val}");
            // Log to audit table if DB is ready
            try {
                db_exec(
                    "INSERT INTO audit_log (user_id, event, detail, ip_address) VALUES (?,?,?,?)",
                    [$_SESSION['user_id'] ?? null, 'sqli_probe', "key={$key}", $ip]
                );
            } catch (\Throwable) {}
            http_response_code(400);
            die(json_encode(['error' => 'Malicious input detected.']));
        }
    }
}

/* ══════════════════════════════════════════
   PASSWORD UTILITIES
══════════════════════════════════════════ */

function hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 2,
    ]);
}

function verify_password(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function needs_rehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_ARGON2ID);
}

/* ══════════════════════════════════════════
   TOKEN GENERATION
══════════════════════════════════════════ */

function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

function constant_compare(string $a, string $b): bool {
    return hash_equals($a, $b);
}

/* ══════════════════════════════════════════
   API KEY (for REST endpoints, future use)
══════════════════════════════════════════ */

function validate_api_key(string $key): bool {
    // In production: lookup hashed key in api_keys table
    // For demo: hardcoded check
    $valid = getenv('SECURETASK_API_KEY') ?: 'demo-api-key-change-me';
    return constant_compare($key, $valid);
}

/* ══════════════════════════════════════════
   SECURITY AUDIT HELPERS
══════════════════════════════════════════ */

function get_security_events(int $user_id, int $limit = 30): array {
    return db_query(
        "SELECT * FROM audit_log WHERE user_id=? AND event IN
         ('login_success','login_failed','logout','csrf_violation','sqli_probe','rate_limit_blocked')
         ORDER BY created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

function get_failed_login_count(string $ip): int {
    $r = db_row(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip_address=? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$ip, RATE_WINDOW_SECONDS]
    );
    return (int)($r['c'] ?? 0);
}