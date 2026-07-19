<?php
/**
 * Ticket Detail — Shared view for all roles (customer, agent, admin)
 * Shows ticket info, reply thread, and role-based actions.
 */
$pageTitle = 'Ticket Detail — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
$pdo = getDbConnection();

$ticketId = (int)($_GET['id'] ?? 0);
if (!$ticketId) { header('Location: dashboard.php'); exit; }

// Handle Delete Ticket Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ticket') {
    // Only customer can delete, or admin
    if ($currentUser['role'] === 'customer' || $currentUser['role'] === 'admin') {
        // Ensure ticket belongs to customer if role is customer
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND (customer_email = ? OR ? = 'admin')");
        $stmt->execute([$ticketId, $currentUser['email'], $currentUser['role']]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?")->execute([$ticketId]);
            $pdo->prepare("DELETE FROM ticket_attachments WHERE ticket_id = ?")->execute([$ticketId]);
            $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticketId]);
            header('Location: my_tickets.php?msg=deleted');
            exit;
        }
    }
}


// Handle actions
$actionMsg = '';
$actionType = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && in_array($currentUser['role'], ['agent','admin'])) {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['New','Open','In Progress','On Hold','Resolved','Closed'])) {
            $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?")->execute([$newStatus, $ticketId]);
            $actionMsg = "Status updated to {$newStatus}";
            $actionType = 'success';
        }
    }

    if ($_POST['action'] === 'add_reply') {
        $message = trim($_POST['message'] ?? '');
        if (!empty($message)) {
            $authorRole = $currentUser['role'] === 'customer' ? 'customer' : 'agent';
            $pdo->prepare("INSERT INTO ticket_replies (ticket_id, author_role, author_name, message) VALUES (?, ?, ?, ?)")
                ->execute([$ticketId, $authorRole, $currentUser['full_name'], $message]);
            $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticketId]);
            $actionMsg = 'Reply sent successfully!';
            $actionType = 'success';
        }
    }

    if ($_POST['action'] === 'regenerate_ai' && in_array($currentUser['role'], ['agent','admin'])) {
        if (file_exists(__DIR__ . '/config/gemini.php')) {
            require_once __DIR__ . '/config/gemini.php';
            $ticket = $pdo->prepare("SELECT title, description FROM tickets WHERE id = ?");
            $ticket->execute([$ticketId]);
            $t = $ticket->fetch();
            if ($t) {
                $aiResult = analyzeTicket($t['title'], $t['description']);
                $newReply = $aiResult['ai_suggested_reply'] ?? '';
                if ($newReply) {
                    $pdo->prepare("UPDATE tickets SET ai_suggested_reply = ? WHERE id = ?")->execute([$newReply, $ticketId]);
                    $actionMsg = 'AI reply regenerated!';
                    $actionType = 'success';
                }
            }
        }
    }

    if ($_POST['action'] === 'assign_agent' && $currentUser['role'] === 'admin') {
        $agentId = (int)($_POST['agent_id'] ?? 0);
        $pdo->prepare("UPDATE tickets SET agent_id = ? WHERE id = ?")->execute([$agentId ?: null, $ticketId]);
        $actionMsg = 'Agent assigned successfully!';
        $actionType = 'success';
    }
}

// Fetch ticket
$stmt = $pdo->prepare("SELECT t.*, a.name AS agent_name, a.email AS agent_email FROM tickets t LEFT JOIN agents a ON t.agent_id = a.id WHERE t.id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="empty-state"><div class="empty-icon">🔍</div><h5>Ticket not found</h5><a href="dashboard.php" class="btn btn-accent mt-2">Back to Dashboard</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Access control for customers
if ($currentUser['role'] === 'customer' && $ticket['customer_email'] !== $currentUser['email']) {
    header('Location: dashboard.php');
    exit;
}

// Fetch replies
$replies = $pdo->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
$replies->execute([$ticketId]);
$replies = $replies->fetchAll();

// Fetch attachments
$attachments = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
$attachments->execute([$ticketId]);
$attachments = $attachments->fetchAll();

// Fetch agents for admin
$agents = [];
if ($currentUser['role'] === 'admin') {
    $agents = $pdo->query("SELECT id, name FROM agents ORDER BY name")->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Back Button & Actions -->
<div class="d-flex justify-content-between align-items-center mb-3 animate-in">
    <a href="javascript:history.back()" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    
    <?php if ($currentUser['role'] === 'customer'): ?>
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket? This cannot be undone.');">
        <input type="hidden" name="action" value="delete_ticket">
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash me-1"></i>Delete Ticket
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if ($actionMsg): ?>
<div class="alert alert-<?= $actionType === 'success' ? 'success' : 'danger' ?> rounded-3 animate-in">
    <i class="bi bi-<?= $actionType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
    <?= e($actionMsg) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Ticket Header -->
        <div class="card-custom p-4 mb-3 animate-in">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-2"><?= e($ticket['title']) ?></h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <?= statusBadge($ticket['status']) ?>
                        <?= priorityBadge($ticket['priority']) ?>
                        <?= sentimentBadge($ticket['sentiment']) ?>
                        <?php if ($ticket['category']): ?>
                            <span class="badge bg-light text-dark border"><?= e($ticket['category']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="text-muted fw-semibold">#<?= $ticket['id'] ?></span>
            </div>

            <div class="p-3 rounded-3" style="background: #F8FAFF;">
                <p class="mb-0" style="white-space: pre-wrap; line-height: 1.7;"><?= e($ticket['description']) ?></p>
            </div>

            <?php if (!empty($attachments)): ?>
            <div class="mt-3">
                <h6 class="fw-semibold small text-muted mb-2"><i class="bi bi-paperclip me-1"></i>Attachments</h6>
                <?php foreach ($attachments as $att): ?>
                    <a href="<?= e($att['filepath']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm me-1 mb-1">
                        <i class="bi bi-file-earmark me-1"></i><?= e($att['filename']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Reply Thread -->
        <div class="card-custom p-4 mb-3 animate-in delay-1">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots me-1"></i>Conversation (<?= count($replies) ?>)</h6>

            <?php if (empty($replies)): ?>
                <p class="text-muted text-center py-3">No replies yet. Start the conversation!</p>
            <?php else: ?>
                <?php foreach ($replies as $r): ?>
                <div class="reply-bubble reply-<?= $r['author_role'] ?> mb-3">
                    <div class="reply-meta d-flex justify-content-between">
                        <span>
                            <?= $r['author_role'] === 'customer' ? '👤' : '🛠️' ?>
                            <strong><?= e($r['author_name']) ?></strong>
                            <span class="badge bg-light text-muted small ms-1"><?= $r['author_role'] ?></span>
                        </span>
                        <span><?= timeAgo($r['created_at']) ?></span>
                    </div>
                    <div class="reply-message mt-1"><?= nl2br(e($r['message'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Reply Form -->
            <form method="POST" class="mt-3 pt-3 border-top">
                <input type="hidden" name="action" value="add_reply">
                <div class="mb-2">
                    <textarea class="form-control" name="message" rows="3" placeholder="Type your reply..." required></textarea>
                </div>
                <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-send me-1"></i>Send Reply</button>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Info Card -->
        <div class="card-custom p-4 mb-3 animate-in delay-2">
            <h6 class="fw-bold mb-3">📋 Details</h6>
            <div class="mb-2"><span class="text-muted small d-block">Customer</span><strong><?= e($ticket['customer_name']) ?></strong></div>
            <div class="mb-2"><span class="text-muted small d-block">Email</span><?= e($ticket['customer_email']) ?></div>
            <div class="mb-2"><span class="text-muted small d-block">Agent</span><?= $ticket['agent_name'] ? e($ticket['agent_name']) : '<em class="text-muted">Unassigned</em>' ?></div>
            <div class="mb-2"><span class="text-muted small d-block">Created</span><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></div>
            <div><span class="text-muted small d-block">Updated</span><?= timeAgo($ticket['updated_at']) ?></div>
        </div>

        <?php if (in_array($currentUser['role'], ['agent','admin'])): ?>
        <!-- Agent Controls -->
        <div class="card-custom p-4 mb-3 animate-in delay-3">
            <h6 class="fw-bold mb-3">🛠️ Actions</h6>

            <!-- Status Update -->
            <form method="POST" class="mb-3">
                <input type="hidden" name="action" value="update_status">
                <label class="form-label small fw-semibold">Update Status</label>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" name="status">
                        <?php foreach (['New','Open','In Progress','On Hold','Resolved','Closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-accent btn-sm">Update</button>
                </div>
            </form>

            <?php if ($currentUser['role'] === 'admin'): ?>
            <!-- Assign Agent -->
            <form method="POST">
                <input type="hidden" name="action" value="assign_agent">
                <label class="form-label small fw-semibold">Assign Agent</label>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" name="agent_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $ticket['agent_id'] == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Assign</button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <!-- AI Suggested Reply -->
        <div class="ai-box animate-in delay-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-robot me-1"></i>AI Suggested Reply</h6>
            <form method="POST">
                <input type="hidden" name="action" value="add_reply">
                <textarea class="form-control mb-2" name="message" rows="4"><?= e($ticket['ai_suggested_reply'] ?? '') ?></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-accent btn-sm flex-fill"><i class="bi bi-send me-1"></i>Send AI Reply</button>
                </div>
            </form>
            <form method="POST" class="mt-2">
                <input type="hidden" name="action" value="regenerate_ai">
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-clockwise me-1"></i>Regenerate</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
