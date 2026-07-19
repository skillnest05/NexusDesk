<?php
/**
 * Dashboard — Role-based landing page with stats
 */
$pageTitle = 'Dashboard — NexusDesk';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';

$pdo = getDbConnection();

// Fetch stats
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('Resolved','Closed')")->fetchColumn();
$urgentTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE priority = 'Urgent' AND status NOT IN ('Resolved','Closed')")->fetchColumn();
$resolvedToday = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolved' AND DATE(updated_at) = CURDATE()")->fetchColumn();

// Role-specific data
if ($user['role'] === 'customer') {
    $myTickets = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE customer_email = ?");
    $myTickets->execute([$user['email']]);
    $myTicketCount = $myTickets->fetchColumn();
}

if ($user['role'] === 'agent') {
    // Find agent record by email
    $agentStmt = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
    $agentStmt->execute([$user['email']]);
    $agentRow = $agentStmt->fetch();
    $agentId = $agentRow ? $agentRow['id'] : 0;

    $queueStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE agent_id = ? AND status NOT IN ('Resolved','Closed')");
    $queueStmt->execute([$agentId]);
    $queueCount = $queueStmt->fetchColumn();
}

// Recent tickets
if ($user['role'] === 'customer') {
    $recentStmt = $pdo->prepare("SELECT * FROM tickets WHERE customer_email = ? ORDER BY updated_at DESC LIMIT 5");
    $recentStmt->execute([$user['email']]);
} elseif ($user['role'] === 'agent') {
    $recentStmt = $pdo->prepare("SELECT * FROM tickets WHERE agent_id = ? ORDER BY updated_at DESC LIMIT 5");
    $recentStmt->execute([$agentId ?? 0]);
} else {
    $recentStmt = $pdo->query("SELECT * FROM tickets ORDER BY updated_at DESC LIMIT 5");
}
$recentTickets = $recentStmt->fetchAll();
?>

<!-- Welcome Banner -->
<div class="welcome-banner mb-4 animate-in">
    <h2>👋 Welcome back, <?= e($user['full_name']) ?>!</h2>
    <p>You're signed in as <strong><?= ucfirst($user['role']) ?></strong>. Here's your overview.</p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php if ($user['role'] === 'customer'): ?>
        <div class="col-sm-6 col-lg-3 animate-in delay-1">
            <div class="stat-card text-center">
                <div class="stat-icon">🎫</div>
                <div class="stat-number"><?= $myTicketCount ?></div>
                <div class="stat-label">My Tickets</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'agent'): ?>
        <div class="col-sm-6 col-lg-3 animate-in delay-1">
            <div class="stat-card text-center">
                <div class="stat-icon">📋</div>
                <div class="stat-number"><?= $queueCount ?? 0 ?></div>
                <div class="stat-label">In My Queue</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'admin'): ?>
        <div class="col-sm-6 col-lg-3 animate-in delay-2">
            <div class="stat-card text-center">
                <div class="stat-icon">📂</div>
                <div class="stat-number"><?= $openTickets ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 animate-in delay-3">
            <div class="stat-card text-center">
                <div class="stat-icon">⚡</div>
                <div class="stat-number"><?= $urgentTickets ?></div>
                <div class="stat-label">Urgent</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 animate-in delay-4">
            <div class="stat-card text-center">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= $resolvedToday ?></div>
                <div class="stat-label">Resolved Today</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 animate-in delay-5">
            <div class="stat-card text-center">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?= $totalTickets ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <?php if ($user['role'] === 'customer'): ?>
        <div class="col-md-6 animate-in delay-3">
            <a href="submit_ticket.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">✉️</div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Submit a New Ticket</h6>
                        <p class="mb-0 text-muted small">Describe your issue and our AI will categorize it</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
        <div class="col-md-6 animate-in delay-4">
            <a href="my_tickets.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">🔍</div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">View My Tickets</h6>
                        <p class="mb-0 text-muted small">Track status and replies on your tickets</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
    <?php elseif ($user['role'] === 'agent'): ?>
        <div class="col-md-6 animate-in delay-3">
            <a href="agent_queue.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">📋</div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">My Ticket Queue</h6>
                        <p class="mb-0 text-muted small">View and respond to assigned tickets</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
    <?php else: ?>
        <div class="col-md-4 animate-in delay-3">
            <a href="admin_tickets.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">🎫</div>
                    <div><h6 class="fw-bold mb-1 text-dark">Manage Tickets</h6></div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
        <div class="col-md-4 animate-in delay-4">
            <a href="admin_analytics.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">📈</div>
                    <div><h6 class="fw-bold mb-1 text-dark">Analytics</h6></div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
        <div class="col-md-4 animate-in delay-5">
            <a href="admin_agents.php" class="card-custom card-clickable p-4 d-block text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem">👥</div>
                    <div><h6 class="fw-bold mb-1 text-dark">Manage Agents</h6></div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Tickets -->
<?php if (!empty($recentTickets)): ?>
<div class="card-custom p-0 animate-in delay-5">
    <div class="p-3 px-4 border-bottom"><h6 class="fw-bold mb-0">📋 Recent Tickets</h6></div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead><tr>
                <th>Title</th><th>Status</th><th>Priority</th><th>Category</th><th>Updated</th>
            </tr></thead>
            <tbody>
                <?php foreach ($recentTickets as $t): ?>
                <tr class="card-clickable" onclick="location.href='ticket_detail.php?id=<?= $t['id'] ?>'">
                    <td class="fw-semibold"><?= e($t['title']) ?></td>
                    <td><?= statusBadge($t['status']) ?></td>
                    <td><?= priorityBadge($t['priority']) ?></td>
                    <td><span class="text-muted small"><?= e($t['category'] ?? '—') ?></span></td>
                    <td class="time-ago"><?= timeAgo($t['updated_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
