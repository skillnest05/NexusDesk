<?php
/**
 * Tickets Collection Endpoint
 * GET  /api/tickets — List tickets with optional filters
 * POST /api/tickets — Create a new ticket (multipart/form-data)
 */

require_once __DIR__ . '/helpers.php';

/**
 * GET /api/tickets
 * Optional query params: status, category, priority, agent_id, customer_email
 */
function handleListTickets(): void {
    $pdo = getDbConnection();
    
    $where = [];
    $params = [];
    
    // Apply optional filters
    if (!empty($_GET['status'])) {
        $where[] = 't.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['category'])) {
        $where[] = 't.category = ?';
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['priority'])) {
        $where[] = 't.priority = ?';
        $params[] = $_GET['priority'];
    }
    if (isset($_GET['agent_id']) && $_GET['agent_id'] !== '') {
        $where[] = 't.agent_id = ?';
        $params[] = (int)$_GET['agent_id'];
    }
    if (!empty($_GET['customer_email'])) {
        $where[] = 't.customer_email = ?';
        $params[] = $_GET['customer_email'];
    }
    
    $sql = '
        SELECT t.*, a.name AS agent_name
        FROM tickets t
        LEFT JOIN agents a ON t.agent_id = a.id
    ';
    
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    
    $sql .= ' ORDER BY t.created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    // Build full response for each ticket
    $result = [];
    foreach ($tickets as $ticket) {
        $result[] = buildTicketResponse($pdo, $ticket);
    }
    
    echo json_encode($result);
}

/**
 * POST /api/tickets
 * Accepts multipart/form-data: title, description, priority, customer_name, customer_email, attachments[]
 */
function handleCreateTicket(): void {
    $pdo = getDbConnection();
    
    // Read form fields (multipart/form-data)
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $priority      = trim($_POST['priority'] ?? 'Medium');
    $customerName  = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = 'title is required';
    if (empty($description)) $errors[] = 'description is required';
    if (empty($customerName)) $errors[] = 'customer_name is required';
    if (empty($customerEmail)) $errors[] = 'customer_email is required';
    
    $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
    if (!in_array($priority, $validPriorities)) {
        $priority = 'Medium';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed: ' . implode(', ', $errors)]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert ticket with status='New'
        $stmt = $pdo->prepare('
            INSERT INTO tickets (title, description, priority, status, customer_name, customer_email)
            VALUES (?, ?, ?, \'New\', ?, ?)
        ');
        $stmt->execute([$title, $description, $priority, $customerName, $customerEmail]);
        $ticketId = (int)$pdo->lastInsertId();
        
        // 2. Handle file uploads
        if (!empty($_FILES['attachments'])) {
            $uploadDir = __DIR__ . '/uploads/' . $ticketId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $files = $_FILES['attachments'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            
            for ($i = 0; $i < $fileCount; $i++) {
                $name    = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error   = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
                $size    = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
                
                if ($error !== UPLOAD_ERR_OK) continue;
                if ($size > 5 * 1024 * 1024) continue; // 5MB max
                
                // Sanitize filename
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
                $safeName = substr($safeName, 0, 200);
                
                // Avoid overwrite
                $destPath = $uploadDir . '/' . $safeName;
                if (file_exists($destPath)) {
                    $safeName = time() . '_' . $safeName;
                    $destPath = $uploadDir . '/' . $safeName;
                }
                
                if (move_uploaded_file($tmpName, $destPath)) {
                    $url = '/uploads/' . $ticketId . '/' . $safeName;
                    $attachStmt = $pdo->prepare('INSERT INTO ticket_attachments (ticket_id, filename, url) VALUES (?, ?, ?)');
                    $attachStmt->execute([$ticketId, $safeName, $url]);
                }
            }
        }
        
        // 3. Call Gemini AI to analyze the ticket
        $aiResult = analyzeTicket($title, $description);
        
        // 4. Update ticket with AI-generated fields
        $updateStmt = $pdo->prepare('
            UPDATE tickets 
            SET category = ?, sentiment = ?, ai_suggested_reply = ?
            WHERE id = ?
        ');
        $updateStmt->execute([
            $aiResult['category'],
            $aiResult['sentiment'],
            $aiResult['suggested_reply'],
            $ticketId
        ]);
        
        $pdo->commit();
        
        // 5. Return the full, AI-enriched ticket object
        $ticket = fetchTicketById($pdo, $ticketId);
        if ($ticket) {
            http_response_code(201);
            echo json_encode(buildTicketResponse($pdo, $ticket));
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ticket created but could not be retrieved']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Create ticket error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
    }
}
