<?php
/**
 * Database Configuration
 * Supports Render, Railway (MYSQL_URL or individual vars), .env file, and local defaults.
 */

// Parse .env file if it exists
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

// Helper to look up an environment variable with multiple aliases and fallbacks
function getDbEnv(array $keys, array $env, $default = '') {
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (isset($env[$key]) && $env[$key] !== '') {
            return $env[$key];
        }
    }
    return $default;
}

// Try full connection string first (MYSQL_URL, DATABASE_URL, CLEARDB_DATABASE_URL, etc.)
$mysqlUrl = getDbEnv(['MYSQL_URL', 'DATABASE_URL', 'CLEARDB_DATABASE_URL', 'JAWSDB_URL'], $env, false);

if ($mysqlUrl) {
    $parsed = parse_url($mysqlUrl);
    $parsedQuery = [];
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $parsedQuery);
    }

    $dbHost = $parsed['host'] ?? '127.0.0.1';
    $dbPort = (string)($parsed['port'] ?? '3306');
    $dbPath = $parsed['path'] ?? '/support_ticket_system';
    $dbName = ltrim(explode('?', $dbPath)[0], '/');
    $dbUser = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
    $dbPass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
} else {
    // Check individual environment variables
    $dbHost = getDbEnv(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'], $env, '127.0.0.1');
    if ($dbHost === 'localhost') { $dbHost = '127.0.0.1'; }
    $dbPort = (string)getDbEnv(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'], $env, '3306');
    $dbName = getDbEnv(['DB_NAME', 'DB_DATABASE', 'MYSQLDATABASE', 'MYSQL_DATABASE'], $env, 'support_ticket_system');
    $dbUser = getDbEnv(['DB_USER', 'DB_USERNAME', 'MYSQLUSER', 'MYSQL_USER'], $env, 'root');
    $dbPass = getDbEnv(['DB_PASS', 'DB_PASSWORD', 'MYSQLPASSWORD', 'MYSQL_PASSWORD'], $env, '');
    $parsedQuery = [];
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', getDbEnv(['DB_CHARSET'], $env, 'utf8mb4'));

// Determine SSL requirement (common for cloud MySQL like TiDB, Aiven, PlanetScale, AWS RDS)
$sslEnv = strtolower(getDbEnv(['DB_SSL', 'MYSQL_SSL'], $env, ''));
$useSsl = ($sslEnv === 'true' || $sslEnv === '1' || $sslEnv === 'require' || $sslEnv === 'required')
    || (isset($parsedQuery['ssl-mode']) && strtolower($parsedQuery['ssl-mode']) !== 'disabled')
    || (isset($parsedQuery['sslmode']) && strtolower($parsedQuery['sslmode']) !== 'disable')
    || (stripos(DB_HOST, 'tidbcloud.com') !== false)
    || (stripos(DB_HOST, 'aivencloud.com') !== false);

define('DB_USE_SSL', $useSsl);

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

        // Configure SSL if required by cloud provider
        if (DB_USE_SSL) {
            $caPaths = [
                '/etc/ssl/certs/ca-certificates.crt', // Debian/Ubuntu/Docker
                '/etc/pki/tls/certs/ca-bundle.crt',   // RHEL/CentOS
                '/etc/ssl/ca-bundle.pem',              // Alpine
            ];
            foreach ($caPaths as $ca) {
                if (file_exists($ca)) {
                    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                    }
                    break;
                }
            }
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            try {
                $pdo->exec("SET time_zone = '+05:30'");
            } catch (Throwable $tzEx) {
                // Ignore timezone set failure if custom offsets are unsupported on cloud instance
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database connection failed: ' . $e->getMessage(),
                'hint'  => 'Ensure your MySQL environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT or DATABASE_URL) are configured in Render.'
            ]);
            exit;
        }
    }
    
    return $pdo;
}
