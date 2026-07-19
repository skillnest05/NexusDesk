<?php
/**
 * Support Ticket System — API Router
 * 
 * Routes all /api/* requests to the appropriate handler.
 * For non-API requests, PHP's built-in server serves .php files directly.
 * This file only activates for /api/* paths or direct / access.
 */

// Error reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Parse the request URI
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . trim($path, '/');

// If not an API route, let the built-in server handle static/PHP files
if (!str_starts_with($path, '/api')) {
    // For root /, redirect to login
    if ($path === '/') {
        header('Location: login.php');
        exit;
    }
    // For existing files (.php, .css, .js, images), let PHP's built-in server handle them
    $filePath = __DIR__ . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        return false; // tells PHP built-in server to serve the file directly
    }
    // File not found
    http_response_code(404);
    echo 'Not Found';
    exit;
}

// CORS headers — allow all origins for development (API only)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load config
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/gemini.php';

$method = $_SERVER['REQUEST_METHOD'];

// ---- Routing ----

// GET /api/tickets — List tickets
// POST /api/tickets — Create ticket
if (preg_match('#^/api/tickets$#', $path)) {
    require_once __DIR__ . '/api/tickets.php';
    if ($method === 'GET') {
        handleListTickets();
    } elseif ($method === 'POST') {
        handleCreateTicket();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// GET /api/tickets/{id} — Single ticket
// PUT /api/tickets/{id} — Update ticket
if (preg_match('#^/api/tickets/(\d+)$#', $path, $matches)) {
    $ticketId = (int)$matches[1];
    require_once __DIR__ . '/api/ticket_detail.php';
    if ($method === 'GET') {
        handleGetTicket($ticketId);
    } elseif ($method === 'PUT') {
        handleUpdateTicket($ticketId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// POST /api/tickets/{id}/replies
if (preg_match('#^/api/tickets/(\d+)/replies$#', $path, $matches)) {
    $ticketId = (int)$matches[1];
    require_once __DIR__ . '/api/ticket_replies.php';
    if ($method === 'POST') {
        handleAddReply($ticketId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// POST /api/tickets/{id}/ai-reply
if (preg_match('#^/api/tickets/(\d+)/ai-reply$#', $path, $matches)) {
    $ticketId = (int)$matches[1];
    require_once __DIR__ . '/api/ticket_ai_reply.php';
    if ($method === 'POST') {
        handleRegenerateAIReply($ticketId);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// GET /api/agents
if (preg_match('#^/api/agents$#', $path)) {
    require_once __DIR__ . '/api/agents.php';
    if ($method === 'GET') {
        handleListAgents();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// GET /api/analytics/summary
if (preg_match('#^/api/analytics/summary$#', $path)) {
    require_once __DIR__ . '/api/analytics.php';
    if ($method === 'GET') {
        handleAnalyticsSummary();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// No route matched
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found: ' . $method . ' ' . $path]);
