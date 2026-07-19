<?php
/**
 * Database Initialization Script for Railway Deployment
 * 
 * Visit /init_db.php?key=YOUR_SECRET_KEY once after deployment to create tables and seed data.
 * Delete this file or change the key after first use.
 */

// Simple security key — change this before deploying
$secretKey = getenv('INIT_DB_KEY') ?: 'nexusdesk-init-2024';

if (($_GET['key'] ?? '') !== $secretKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid key. Use ?key=YOUR_SECRET_KEY']);
    exit;
}

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

$results = [];

/**
 * Execute a SQL file statement by statement, ignoring "already exists" errors.
 */
function executeSqlFile(PDO $pdo, string $filePath, array &$results): void {
    $sql = file_get_contents($filePath);
    $filename = basename($filePath);
    
    // Remove SQL comments (lines starting with --)
    $sql = preg_replace('/^--.*$/m', '', $sql);
    
    // Split by semicolons into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $success++;
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            // Ignore "already exists" type errors (1061=duplicate key, 1050=table exists, 1062=duplicate entry)
            if (in_array((int)$code, [1061, 1050, 1062]) || 
                strpos($msg, 'already exists') !== false ||
                strpos($msg, 'Duplicate') !== false) {
                $skipped++;
            } else {
                $errors[] = $msg;
            }
        }
    }
    
    $status = empty($errors) ? 'OK' : 'PARTIAL';
    $results[] = "{$filename}: {$success} executed, {$skipped} skipped (already exist)" . 
                 (empty($errors) ? '' : ', ERRORS: ' . implode(' | ', $errors));
}

try {
    $pdo = getDbConnection();
    
    // ---- Schema: agents, tickets, ticket_attachments, ticket_replies ----
    executeSqlFile($pdo, __DIR__ . '/schema.sql', $results);
    
    // ---- Schema: users table ----
    executeSqlFile($pdo, __DIR__ . '/users_schema.sql', $results);
    
    // ---- Seed data ----
    executeSqlFile($pdo, __DIR__ . '/seed.sql', $results);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully!',
        'details' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'completed_steps' => $results
    ], JSON_PRETTY_PRINT);
}
