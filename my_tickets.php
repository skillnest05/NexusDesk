<?php
/**
 * Customer: My Tickets
 */
$pageTitle = 'My Tickets — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'customer') { header('Location: dashboard.php'); exit; }

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE customer_email = ? ORDER BY updated_at DESC");
$stmt->execute([$currentUser['email']]);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-in">
    <div>
        <h4 class="fw-bold mb-1">🎫 My Tickets</h4>
        <p class="text-muted mb-0"><?= count($tickets) ?> ticket(s) found for <?= e($currentUser['email']) ?></p>
    </div>
    <a href="submit_ticket.php" class="btn btn-accent btn-sm"><i class="bi bi-plus-circle me-1"></i>New Ticket</a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
<div class="alert alert-success rounded-3 animate-in">
    <i class="bi bi-check-circle me-1"></i>Ticket successfully deleted.
</div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <div class="empty-state animate-in delay-1">
        <div class="empty-icon">📭</div>
        <h5>No tickets yet</h5>
        <p>You haven't submitted any tickets. Create your first one!</p>
        <a href="submit_ticket.php" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i>Submit Ticket</a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($tickets as $i => $t): ?>
        <div class="col-md-6 col-lg-4 animate-in delay-<?= min($i + 1, 5) ?>">
            <div class="card-custom p-3 position-relative">
                <form method="POST" action="ticket_detail.php?id=<?= $t['id'] ?>" class="position-absolute" style="top:12px; right:12px; z-index:2;" onsubmit="return confirm('Delete this ticket?');">
                    <input type="hidden" name="action" value="delete_ticket">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete Ticket"><i class="bi bi-trash"></i></button>
                </form>
                
                <div class="card-clickable" onclick="location.href='ticket_detail.php?id=<?= $t['id'] ?>'">
                    <div class="d-flex justify-content-between align-items-start mb-2 pe-4">
                        <h6 class="fw-bold mb-0 truncate" title="<?= e($t['title']) ?>"><?= e($t['title']) ?></h6>
                        <span class="text-muted small ms-2">#<?= $t['id'] ?></span>
                    </div>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <?= statusBadge($t['status']) ?>
                    <?= priorityBadge($t['priority']) ?>
                </div>
                <?php if ($t['category']): ?>
                    <span class="badge bg-light text-dark small border"><?= e($t['category']) ?></span>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                    <?= sentimentBadge($t['sentiment']) ?>
                    <span class="time-ago"><?= timeAgo($t['updated_at']) ?></span>
                </div>
                </div> <!-- End card-clickable -->
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
