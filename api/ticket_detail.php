<?php
/**
 * Ticket Detail Endpoint
 * GET /api/tickets/{id} — Get single ticket
 * PUT /api/tickets/{id} — Update ticket (status, agent_id, priority)
 */

require_once __DIR__ . '/helpers.php';

/**
 * GET /api/tickets/{id}
 */
function handleGetTicket(int $ticketId): void {
    $pdo = getDbConnection();
    
    $ticket = fetchTicketById($pdo, $ticketId);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        return;
    }
    
    echo json_encode(buildTicketResponse($pdo, $ticket));
}

/**
 * PUT /api/tickets/{id}
 * Accepts JSON body with any of: status, agent_id, priority
 */
function handleUpdateTicket(int $ticketId): void {
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
    
    $updates = [];
    $params  = [];
    
    // Status update
    if (isset($input['status'])) {
        $validStatuses = ['New', 'Open', 'In Progress', 'On Hold', 'Resolved', 'Closed'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status value. Must be one of: ' . implode(', ', $validStatuses)]);
            return;
        }
        $updates[] = 'status = ?';
        $params[] = $input['status'];
        
        // If resolving, set resolved_at
        if ($input['status'] === 'Resolved' && $ticket['status'] !== 'Resolved') {
            $updates[] = 'resolved_at = NOW()';
        }
        // If re-opening from resolved/closed, clear resolved_at
        if (in_array($ticket['status'], ['Resolved', 'Closed']) && !in_array($input['status'], ['Resolved', 'Closed'])) {
            $updates[] = 'resolved_at = NULL';
        }
    }
    
    // Agent assignment
    if (array_key_exists('agent_id', $input)) {
        if ($input['agent_id'] === null) {
            $updates[] = 'agent_id = NULL';
        } else {
            // Validate agent exists
            $agentCheck = $pdo->prepare('SELECT id FROM agents WHERE id = ?');
            $agentCheck->execute([(int)$input['agent_id']]);
            if (!$agentCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Agent not found']);
                return;
            }
            $updates[] = 'agent_id = ?';
            $params[] = (int)$input['agent_id'];
        }
    }
    
    // Priority update
    if (isset($input['priority'])) {
        $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
        if (!in_array($input['priority'], $validPriorities)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid priority value. Must be one of: ' . implode(', ', $validPriorities)]);
            return;
        }
        $updates[] = 'priority = ?';
        $params[] = $input['priority'];
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update. Accepted fields: status, agent_id, priority']);
        return;
    }
    
    // Execute update
    $params[] = $ticketId;
    $sql = 'UPDATE tickets SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Return updated ticket
    $updatedTicket = fetchTicketById($pdo, $ticketId);
    echo json_encode(buildTicketResponse($pdo, $updatedTicket));
}
