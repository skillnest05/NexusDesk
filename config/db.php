<?php
/**
 * Database Configuration
 * Uses XAMPP MySQL defaults. Update if your setup differs.
 */

// Parse .env file
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

// Railway provides MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD
// Check getenv() first (Railway), then .env file, then XAMPP defaults
define('DB_HOST', getenv('MYSQLHOST') ?: ($env['DB_HOST'] ?? 'localhost'));
define('DB_PORT', getenv('MYSQLPORT') ?: ($env['DB_PORT'] ?? '3306'));
define('DB_NAME', getenv('MYSQLDATABASE') ?: ($env['DB_NAME'] ?? 'support_ticket_system'));
define('DB_USER', getenv('MYSQLUSER') ?: ($env['DB_USER'] ?? 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: ($env['DB_PASS'] ?? ''));
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
