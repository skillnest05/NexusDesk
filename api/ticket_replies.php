<?php
/**
 * Ticket Replies Endpoint
 * POST /api/tickets/{id}/replies — Add a reply to a ticket
 */

require_once __DIR__ . '/helpers.php';

/**
 * POST /api/tickets/{id}/replies
 * JSON body: { author_role, author_name, message }
 */
function handleAddReply(int $ticketId): void {
    $pdo = getDbConnection();
    
    // Check ticket exists
    $ticket = fetchTicketById($pdo, $ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        return;
    }
    
    // Parse JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        return;
    }
    
    $authorRole = trim($input['author_role'] ?? '');
    $authorName = trim($input['author_name'] ?? '');
    $message    = trim($input['message'] ?? '');
    
    // Validation
    $errors = [];
    if (!in_array($authorRole, ['customer', 'agent'])) {
        $errors[] = 'author_role must be "customer" or "agent"';
    }
    if (empty($authorName)) {
        $errors[] = 'author_name is required';
    }
    if (empty($message)) {
        $errors[] = 'message is required';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed: ' . implode(', ', $errors)]);
        return;
    }
    
    try {
        // Insert reply
        $stmt = $pdo->prepare('
            INSERT INTO ticket_replies (ticket_id, author_role, author_name, message)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$ticketId, $authorRole, $authorName, $message]);
        
        // Update ticket's updated_at
        $updateStmt = $pdo->prepare('UPDATE tickets SET updated_at = NOW() WHERE id = ?');
        $updateStmt->execute([$ticketId]);
        
        // Return updated ticket with all replies
        $updatedTicket = fetchTicketById($pdo, $ticketId);
        echo json_encode(buildTicketResponse($pdo, $updatedTicket));
        
    } catch (Exception $e) {
        error_log('Add reply error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add reply']);
    }
}
