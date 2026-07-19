<?php
/**
 * Database Configuration
 * Supports Railway (MYSQL_URL or individual vars), .env file, and XAMPP defaults.
 */

// Parse .env file
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

// Try to parse MYSQL_URL first (Railway provides this as a full connection string)
$mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: false;

if ($mysqlUrl) {
    $parsed = parse_url($mysqlUrl);
    define('DB_HOST', $parsed['host'] ?? '127.0.0.1');
    define('DB_PORT', (string)($parsed['port'] ?? '3306'));
    define('DB_NAME', ltrim($parsed['path'] ?? '/railway', '/'));
    define('DB_USER', $parsed['user'] ?? 'root');
    define('DB_PASS', $parsed['pass'] ?? '');
} else {
    // Fallback: individual env vars (Railway also sets MYSQLHOST, MYSQL_HOST, etc.)
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
    // Force TCP connection: 'localhost' uses Unix socket on Linux which fails in Docker
    if ($host === 'localhost') { $host = '127.0.0.1'; }
    
    define('DB_HOST', $host);
    define('DB_PORT', getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: ($env['DB_PORT'] ?? '3306'));
    define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: ($env['DB_NAME'] ?? 'support_ticket_system'));
    define('DB_USER', getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: ($env['DB_USER'] ?? 'root'));
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: ($env['DB_PASS'] ?? ''));
}

define('DB_CHARSET', $env['DB_CHARSET'] ?? 'utf8mb4');

date_default_timezone_set('Asia/Kolkata');

/**
 * Get PDO database connection
 * @return PDO
 */
function getDbConnection(): PDO {
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
            $pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    
    return $pdo;
}

