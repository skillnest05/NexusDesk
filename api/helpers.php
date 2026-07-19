<?php
/**
 * Shared helper functions for building ticket response objects.
 */

/**
 * Fetch attachments for a ticket.
 * 
 * @param PDO $pdo
 * @param int $ticketId
 * @return array
 */
function getTicketAttachments(PDO $pdo, int $ticketId): array {
    $stmt = $pdo->prepare('SELECT filename, url FROM ticket_attachments WHERE ticket_id = ? ORDER BY created_at ASC');
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

/**
 * Fetch replies for a ticket.
 * 
 * @param PDO $pdo
 * @param int $ticketId
 * @return array
 */
function getTicketReplies(PDO $pdo, int $ticketId): array {
    $stmt = $pdo->prepare('SELECT id, author_role, author_name, message, created_at FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC');
    $stmt->execute([$ticketId]);
    $replies = $stmt->fetchAll();
    // Cast id to int
    foreach ($replies as &$reply) {
        $reply['id'] = (int)$reply['id'];
    }
    return $replies;
}

/**
 * Build a full ticket response object (with agent_name, attachments, replies).
 * 
 * @param PDO $pdo
 * @param array $ticket Raw ticket row
 * @return array Formatted ticket object
 */
function buildTicketResponse(PDO $pdo, array $ticket): array {
    $ticketId = (int)$ticket['id'];
    
    return [
        'id'                => $ticketId,
        'title'             => $ticket['title'],
        'description'       => $ticket['description'],
        'category'          => $ticket['category'],
        'priority'          => $ticket['priority'],
        'status'            => $ticket['status'],
        'sentiment'         => $ticket['sentiment'],
        'ai_suggested_reply'=> $ticket['ai_suggested_reply'],
        'customer_name'     => $ticket['customer_name'],
        'customer_email'    => $ticket['customer_email'],
        'agent_id'          => $ticket['agent_id'] !== null ? (int)$ticket['agent_id'] : null,
        'agent_name'        => $ticket['agent_name'] ?? null,
        'attachments'       => getTicketAttachments($pdo, $ticketId),
        'replies'           => getTicketReplies($pdo, $ticketId),
        'created_at'        => $ticket['created_at'],
        'updated_at'        => $ticket['updated_at'],
    ];
}

/**
 * Fetch a single ticket row by ID (with agent_name JOIN).
 * Returns null if not found.
 * 
 * @param PDO $pdo
 * @param int $ticketId
 * @return array|null
 */
function fetchTicketById(PDO $pdo, int $ticketId): ?array {
    $stmt = $pdo->prepare('
        SELECT t.*, a.name AS agent_name
        FROM tickets t
        LEFT JOIN agents a ON t.agent_id = a.id
        WHERE t.id = ?
    ');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    return $ticket ?: null;
}
