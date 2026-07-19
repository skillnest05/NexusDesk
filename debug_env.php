<?php
/**
 * Debug endpoint — shows which MySQL env vars Railway has set.
 * DELETE THIS FILE after deployment is working.
 */
header('Content-Type: application/json');

echo json_encode([
    'MYSQL_URL'       => getenv('MYSQL_URL') ? 'SET (hidden)' : 'NOT SET',
    'DATABASE_URL'    => getenv('DATABASE_URL') ? 'SET (hidden)' : 'NOT SET',
    'MYSQLHOST'       => getenv('MYSQLHOST') ?: 'NOT SET',
    'MYSQL_HOST'      => getenv('MYSQL_HOST') ?: 'NOT SET',
    'MYSQLPORT'       => getenv('MYSQLPORT') ?: 'NOT SET',
    'MYSQL_PORT'      => getenv('MYSQL_PORT') ?: 'NOT SET',
    'MYSQLDATABASE'   => getenv('MYSQLDATABASE') ?: 'NOT SET',
    'MYSQL_DATABASE'  => getenv('MYSQL_DATABASE') ?: 'NOT SET',
    'MYSQLUSER'       => getenv('MYSQLUSER') ?: 'NOT SET',
    'GEMINI_API_KEY'  => getenv('GEMINI_API_KEY') ? 'SET (hidden)' : 'NOT SET',
    'GMAIL_USER'      => getenv('GMAIL_USER') ?: 'NOT SET',
    'php_extensions'  => get_loaded_extensions(),
], JSON_PRETTY_PRINT);
