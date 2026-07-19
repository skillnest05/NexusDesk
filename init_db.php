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

try {
    $pdo = getDbConnection();
    
    // ---- Schema: agents, tickets, ticket_attachments, ticket_replies ----
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    $results[] = 'schema.sql executed successfully';
    
    // ---- Schema: users table ----
    $usersSchema = file_get_contents(__DIR__ . '/users_schema.sql');
    $pdo->exec($usersSchema);
    $results[] = 'users_schema.sql executed successfully';
    
    // ---- Seed data ----
    $seed = file_get_contents(__DIR__ . '/seed.sql');
    $pdo->exec($seed);
    $results[] = 'seed.sql executed successfully';
    
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
