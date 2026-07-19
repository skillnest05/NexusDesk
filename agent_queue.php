<?php
/**
 * Agent: Assigned Ticket Queue
 */
$pageTitle = 'My Queue — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'agent') { header('Location: dashboard.php'); exit; }

$pdo = getDbConnection();

// Find this agent's ID
$agentStmt = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
$agentStmt->execute([$currentUser['email']]);
$agentRow = $agentStmt->fetch();
$agentId = $agentRow ? $agentRow['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE agent_id = ? ORDER BY FIELD(priority, 'Urgent','High','Medium','Low'), updated_at DESC");
$stmt->execute([$agentId]);
$tickets = $stmt->fetchAll();

// Calculate Dashboard Statistics
$unsolvedCount = 0;
$solvedCount = 0;
$totalAssigned = count($tickets);

foreach ($tickets as $t) {
    if (in_array($t['status'], ['Resolved', 'Closed'])) {
        $solvedCount++;
    } else {
        $unsolvedCount++;
    }
}

// Filters (client-side applied via JS)
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-in">
    <div>
        <h4 class="fw-bold mb-1">📋 My Dashboard</h4>
        <p class="text-muted mb-0">Overview of your assigned work</p>
    </div>
</div>

<!-- Dashboard Statistics Cards -->
<div class="row g-3 mb-4 animate-in delay-1">
    <div class="col-md-4">
        <div class="card-custom p-3" style="border-left: 4px solid #F59E0B;">
            <div class="text-muted small fw-semibold text-uppercase tracking-wider">Unsolved Tasks</div>
            <div class="fs-2 fw-bold text-dark mt-1"><?= $unsolvedCount ?></div>
            <div class="small text-muted mt-1">Pending your action</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3" style="border-left: 4px solid #10B981;">
            <div class="text-muted small fw-semibold text-uppercase tracking-wider">Solved Tickets</div>
            <div class="fs-2 fw-bold text-dark mt-1"><?= $solvedCount ?></div>
            <div class="small text-muted mt-1">Resolved or Closed</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3" style="border-left: 4px solid #6366F1;">
            <div class="text-muted small fw-semibold text-uppercase tracking-wider">Total Assigned</div>
            <div class="fs-2 fw-bold text-dark mt-1"><?= $totalAssigned ?></div>
            <div class="small text-muted mt-1">All time</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar d-flex gap-3 flex-wrap animate-in delay-2">
    <div>
        <label class="form-label small fw-semibold mb-1">Priority</label>
        <select class="form-select form-select-sm" id="filterPriority" onchange="applyFilters()">
            <option value="">All Priorities</option>
            <option value="Urgent">🔴 Urgent</option>
            <option value="High">🟠 High</option>
            <option value="Medium">🔵 Medium</option>
            <option value="Low">🟢 Low</option>
        </select>
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Status</label>
        <select class="form-select form-select-sm" id="filterStatus" onchange="applyFilters()">
            <option value="">All Statuses</option>
            <option value="New">New</option>
            <option value="Open">Open</option>
            <option value="In Progress">In Progress</option>
            <option value="On Hold">On Hold</option>
            <option value="Resolved">Resolved</option>
            <option value="Closed">Closed</option>
        </select>
    </div>
</div>

<?php if (empty($tickets)): ?>
    <div class="empty-state animate-in delay-2">
        <div class="empty-icon">📭</div>
        <h5>No tickets in your queue</h5>
        <p>You don't have any assigned tickets right now.</p>
    </div>
<?php else: ?>
    <div class="row g-3" id="ticketGrid">
        <?php foreach ($tickets as $i => $t): ?>
        <div class="col-md-6 col-lg-4 ticket-card animate-in delay-<?= min($i + 2, 5) ?>"
             data-priority="<?= e($t['priority']) ?>" data-status="<?= e($t['status']) ?>">
            <div class="card-custom card-clickable p-3" onclick="location.href='ticket_detail.php?id=<?= $t['id'] ?>'">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0 truncate" title="<?= e($t['title']) ?>"><?= e($t['title']) ?></h6>
                    <span class="text-muted small ms-2">#<?= $t['id'] ?></span>
                </div>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <?= statusBadge($t['status']) ?>
                    <?= priorityBadge($t['priority']) ?>
                </div>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <?= sentimentBadge($t['sentiment']) ?>
                    <?php if ($t['category']): ?>
                        <span class="badge bg-light text-dark small border"><?= e($t['category']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="small text-muted"><i class="bi bi-person me-1"></i><?= e($t['customer_name']) ?></span>
                    <span class="time-ago"><?= timeAgo($t['updated_at']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
function applyFilters() {
    const priority = document.getElementById('filterPriority').value;
    const status = document.getElementById('filterStatus').value;
    document.querySelectorAll('.ticket-card').forEach(card => {
        const pMatch = !priority || card.dataset.priority === priority;
        const sMatch = !status || card.dataset.status === status;
        card.style.display = (pMatch && sMatch) ? '' : 'none';
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
