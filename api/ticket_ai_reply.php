<?php
/**
 * AI Reply Regeneration Endpoint
 * POST /api/tickets/{id}/ai-reply — Regenerate AI-suggested reply using full context
 */

require_once __DIR__ . '/helpers.php';

/**
 * POST /api/tickets/{id}/ai-reply
 * No body required. Re-runs Gemini with full ticket context.
 * Returns { "ai_suggested_reply": "..." }
 */
function handleRegenerateAIReply(int $ticketId): void {
    $pdo = getDbConnection();
    
    // Check ticket exists
    $ticket = fetchTicketById($pdo, $ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        return;
    }
    
    // Get all replies for context
    $replies = getTicketReplies($pdo, $ticketId);
    
    // Call Gemini to regenerate reply
    $suggestedReply = regenerateAIReply(
        $ticket['title'],
        $ticket['description'],
        $replies
    );
    
    // Update the ticket with the new AI reply
    $stmt = $pdo->prepare('UPDATE tickets SET ai_suggested_reply = ? WHERE id = ?');
    $stmt->execute([$suggestedReply, $ticketId]);
    
    echo json_encode(['ai_suggested_reply' => $suggestedReply]);
}
