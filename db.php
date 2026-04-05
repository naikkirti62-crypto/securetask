<?php
/**
 * SecureTask — db.php
 * CHANGE DB_NAME, DB_USER, DB_PASS to match your setup
 * XAMPP default: DB_USER=root, DB_PASS=''
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'securetask');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            die('
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DB Error</title>
<style>
body{font-family:Arial,sans-serif;background:#0d0d14;color:#f0f0ff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#13131c;border:1px solid #f43f5e;border-radius:12px;padding:2rem;max-width:550px;width:90%}
h2{color:#f43f5e;margin:0 0 1rem}
code{background:#1a1a26;padding:3px 8px;border-radius:4px;color:#00d4ff;font-size:13px}
p{color:#9090b0;font-size:14px;line-height:1.7;margin:.5rem 0}
</style></head><body>
<div class="box">
<h2>&#9888; Database Connection Failed</h2>
<p><strong style="color:#fff">Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
<p>Open <code>db.php</code> and fix your credentials:</p>
<p><code>DB_NAME</code> = your database name (e.g. <code>securetask</code>)<br>
<code>DB_USER</code> = your MySQL username (XAMPP = <code>root</code>)<br>
<code>DB_PASS</code> = your MySQL password (XAMPP = <code>empty</code>)</p>
<p>Then run <code>reset.sql</code> in phpMyAdmin to create the tables.</p>
</div></body></html>');
        }
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_row(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_exec(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->rowCount();
}

function db_last_id(): int {
    return (int) db()->lastInsertId();
}