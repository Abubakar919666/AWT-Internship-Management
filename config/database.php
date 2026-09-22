<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Database Connection & Query Wrapper (PDO)
 */

if (!defined('AWT_APP')) {
    define('AWT_APP', true);
}

// Default database configuration settings
define('DB_CHARSET', 'utf8mb4');

/**
 * Get the singleton PDO database instance with multi-environment fallback
 *
 * @return PDO
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Environment override or prioritized credential list
        $configs = [];
        
        if (getenv('DB_NAME') && getenv('DB_USER')) {
            $configs[] = [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => getenv('DB_PORT') ?: '3306',
                'name' => getenv('DB_NAME'),
                'user' => getenv('DB_USER'),
                'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''
            ];
        }

        // 1. Live Plesk Production Credentials
        $configs[] = [
            'host' => 'localhost',
            'port' => '3306',
            'name' => 'intern',
            'user' => 'intern',
            'pass' => '4DI*Id845'
        ];
        $configs[] = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'name' => 'intern',
            'user' => 'intern',
            'pass' => '4Dl*ld845'
        ];
        $configs[] = [
            'host' => 'localhost',
            'port' => '3306',
            'name' => 'intern',
            'user' => 'iternawt',
            'pass' => 'e^18bD4q3'
        ];

        // 2. Local Development (XAMPP / Laragon / WAMP)
        $configs[] = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'name' => 'awt_internship',
            'user' => 'root',
            'pass' => ''
        ];
        $configs[] = [
            'host' => 'localhost',
            'port' => '3306',
            'name' => 'intern',
            'user' => 'root',
            'pass' => ''
        ];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        $lastException = null;
        foreach ($configs as $cfg) {
            try {
                $dsn = "mysql:host=" . $cfg['host'] . ";port=" . $cfg['port'] . ";dbname=" . $cfg['name'] . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        // For production security, do not expose raw credentials or traces to end-users
        error_log("Database connection error: " . ($lastException ? $lastException->getMessage() : 'No configs matched'));
        die("
            <div style='font-family:Segoe UI,Tahoma,sans-serif;max-width:600px;margin:50px auto;padding:24px;border:1px solid #f5c6cb;background:#f8d7da;color:#721c24;border-radius:8px;'>
                <h3 style='margin-top:0;'>Database Connection Notice</h3>
                <p>Unable to connect to the AWT Internship database. Please ensure your database server is running and credentials in <code>config/database.php</code> are configured properly.</p>
                <p style='font-size:13px;color:#491217;'>Error details: " . htmlspecialchars($lastException ? $lastException->getMessage() : 'Unknown error') . "</p>
            </div>
        ");
    }

    return $pdo;
}

/**
 * Execute a prepared SQL statement
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $db = get_db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row as associative array
 */
function db_fetch_one(string $sql, array $params = []): ?array {
    $stmt = db_query($sql, $params);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Fetch all matching rows
 */
function db_fetch_all(string $sql, array $params = []): array {
    $stmt = db_query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get last insert ID
 */
function db_last_insert_id(): string {
    return get_db()->lastInsertId();
}

/**
 * Fetch a single setting value from settings table
 */
function get_setting(string $key, string $default = ''): string {
    static $cached = [];
    if (isset($cached[$key])) {
        return $cached[$key];
    }
    try {
        $row = db_fetch_one("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        $val = $row ? (string)$row['setting_value'] : $default;
        $cached[$key] = $val;
        return $val;
    } catch (Exception $e) {
        return $default;
    }
}
