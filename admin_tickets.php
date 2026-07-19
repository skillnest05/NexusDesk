<?php
/**
 * Admin: All Tickets — Full table with filters and agent assignment
 */
$pageTitle = 'All Tickets — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pdo = getDbConnection();

// Handle inline agent assignment
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket_id'])) {
    $tid = (int)$_POST['assign_ticket_id'];
    $aid = (int)($_POST['agent_id'] ?? 0);
    $pdo->prepare("UPDATE tickets SET agent_id = ? WHERE id = ?")->execute([$aid ?: null, $tid]);
    $actionMsg = "Agent assigned to ticket #{$tid}";
}

$tickets = $pdo->query("SELECT t.*, a.name AS agent_name FROM tickets t LEFT JOIN agents a ON t.agent_id = a.id ORDER BY t.updated_at DESC")->fetchAll();
$agents = $pdo->query("SELECT id, name FROM agents ORDER BY name")->fetchAll();

// Extract unique categories
$categories = array_unique(array_filter(array_column($tickets, 'category')));
sort($categories);

require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-1 animate-in">🎫 All Tickets</h4>
<p class="text-muted mb-4 animate-in delay-1"><?= count($tickets) ?> total tickets</p>

<?php if ($actionMsg): ?>
<div class="alert alert-success rounded-3 animate-in"><i class="bi bi-check-circle me-1"></i><?= e($actionMsg) ?></div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="filter-bar d-flex gap-3 flex-wrap animate-in delay-1">
    <div>
        <label class="form-label small fw-semibold mb-1">Status</label>
        <select class="form-select form-select-sm" id="fStatus" onchange="filterTable()">
            <option value="">All</option>
            <?php foreach (['New','Open','In Progress','On Hold','Resolved','Closed'] as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Priority</label>
        <select class="form-select form-select-sm" id="fPriority" onchange="filterTable()">
            <option value="">All</option>
            <option value="Urgent">Urgent</option><option value="High">High</option>
            <option value="Medium">Medium</option><option value="Low">Low</option>
        </select>
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Category</label>
        <select class="form-select form-select-sm" id="fCategory" onchange="filterTable()">
            <option value="">All</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>"><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Agent</label>
        <select class="form-select form-select-sm" id="fAgent" onchange="filterTable()">
            <option value="">All</option>
            <option value="unassigned">Unassigned</option>
            <?php foreach ($agents as $a): ?>
                <option value="<?= e($a['name']) ?>"><?= e($a['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if (empty($tickets)): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><h5>No tickets</h5></div>
<?php else: ?>
<div class="card-custom p-0 animate-in delay-2">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead><tr>
                <th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Category</th>
                <th>Customer</th><th>Agent</th><th>Updated</th>
            </tr></thead>
            <tbody id="ticketTable">
                <?php foreach ($tickets as $t): ?>
                <tr data-status="<?= e($t['status']) ?>" data-priority="<?= e($t['priority']) ?>"
                    data-category="<?= e($t['category'] ?? '') ?>" data-agent="<?= e($t['agent_name'] ?? 'unassigned') ?>">
                    <td class="fw-semibold text-accent">#<?= $t['id'] ?></td>
                    <td>
                        <a href="ticket_detail.php?id=<?= $t['id'] ?>" class="text-decoration-none fw-semibold text-dark truncate d-block" title="<?= e($t['title']) ?>">
                            <?= e($t['title']) ?>
                        </a>
                    </td>
                    <td><?= statusBadge($t['status']) ?></td>
                    <td><?= priorityBadge($t['priority']) ?></td>
                    <td class="small text-muted"><?= e($t['category'] ?? '—') ?></td>
                    <td class="small"><?= e($t['customer_name']) ?></td>
                    <td>
                        <form method="POST" class="d-inline" onchange="this.submit()">
                            <input type="hidden" name="assign_ticket_id" value="<?= $t['id'] ?>">
                            <select name="agent_id" class="form-select form-select-sm" style="min-width:130px; font-size:0.8rem;">
                                <option value="">Unassigned</option>
                                <?php foreach ($agents as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $t['agent_id'] == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="time-ago"><?= timeAgo($t['updated_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
function filterTable() {
    const s = document.getElementById('fStatus').value;
    const p = document.getElementById('fPriority').value;
    const c = document.getElementById('fCategory').value;
    const a = document.getElementById('fAgent').value;
    document.querySelectorAll('#ticketTable tr').forEach(row => {
        const ms = !s || row.dataset.status === s;
        const mp = !p || row.dataset.priority === p;
        const mc = !c || row.dataset.category === c;
        const ma = !a || row.dataset.agent === a;
        row.style.display = (ms && mp && mc && ma) ? '' : 'none';
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
