<?php
/**
 * Admin: Agents — List of agents with stats, plus create/remove logic
 */
$pageTitle = 'Agents — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pdo = getDbConnection();
$error = '';
$success = '';

// Handle Create and Delete Agent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_agent') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $expertise = $_POST['expertise'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($expertise)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if email already exists in users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "This email is already registered.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert into users
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'agent')");
                    $stmt->execute([$name, $email, $hash]);
                    
                    // Insert into agents
                    $stmt = $pdo->prepare("INSERT INTO agents (name, email, expertise) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $expertise]);
                    
                    $pdo->commit();
                    $success = "Agent {$name} created successfully!";
                    
                    // Send Welcome Email
                    require_once __DIR__ . '/config/mail.php';
                    $subject = "Welcome to NexusDesk, {$name}!";
                    $body = "
                        <h2>Welcome to NexusDesk Support Team!</h2>
                        <p>Hi {$name},</p>
                        <p>An administrator has created an agent account for you.</p>
                        <p><b>Login Details:</b><br>
                        Email: {$email}<br>
                        Password: {$password}</p>
                        <p>Your expertise category is set to: <b>{$expertise}</b></p>
                        <p><a href='http://localhost:8000/login.php'>Click here to login</a></p>
                    ";
                    if (!sendSystemEmail($email, $subject, $body)) {
                        $error = "Agent created, but failed to send welcome email.";
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to create agent: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_agent') {
        $agentEmail = $_POST['agent_email'] ?? '';
        
        if (!empty($agentEmail)) {
            try {
                $pdo->beginTransaction();
                
                // Delete from agents table
                $stmt = $pdo->prepare("DELETE FROM agents WHERE email = ?");
                $stmt->execute([$agentEmail]);
                
                // Set user role to suspended_agent in users table
                $stmt = $pdo->prepare("UPDATE users SET role = 'suspended_agent' WHERE email = ? AND role = 'agent'");
                $stmt->execute([$agentEmail]);
                
                $pdo->commit();
                $success = "Agent removed successfully. They can no longer log in as an agent.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to remove agent: " . $e->getMessage();
            }
        }
    }
}

$agents = $pdo->query("
    SELECT a.*,
           COUNT(CASE WHEN t.status NOT IN ('Resolved','Closed') THEN 1 END) AS active_count,
           COUNT(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 END) AS resolved_count,
           ROUND(AVG(CASE WHEN t.status IN ('Resolved','Closed')
                THEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) END), 1) AS avg_hours
    FROM agents a LEFT JOIN tickets t ON a.id = t.agent_id
    GROUP BY a.id ORDER BY a.name
")->fetchAll();

$categories = [
    'Technical Support',
    'Billing & Payments',
    'Account Management',
    'Hardware Issues',
    'Software Bugs',
    'Feature Requests'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-in">
    <div>
        <h4 class="fw-bold mb-1">👥 Agents</h4>
        <p class="text-muted mb-0"><?= count($agents) ?> support agents</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAgentModal">
        <i class="bi bi-person-plus me-1"></i> Add Agent
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger animate-in"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success animate-in"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (empty($agents)): ?>
    <div class="empty-state animate-in delay-2">
        <div class="empty-icon">👥</div>
        <h5>No agents found</h5>
        <p>No support agents have been added to the system.</p>
    </div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($agents as $i => $a): ?>
    <div class="col-md-6 col-lg-4 animate-in delay-<?= min($i + 1, 5) ?>">
        <div class="card-custom p-4 position-relative">
            <form method="POST" class="position-absolute top-0 end-0 mt-3 me-3" onsubmit="return confirm('Are you sure you want to remove this agent? They will no longer be able to log in.');">
                <input type="hidden" name="action" value="delete_agent">
                <input type="hidden" name="agent_email" value="<?= e($a['email']) ?>">
                <button type="submit" class="btn btn-light btn-sm text-danger" title="Remove Agent">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
            
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;font-weight:700;">
                    <?= strtoupper(substr($a['name'], 0, 1)) ?>
                </div>
                <div>
                    <h6 class="fw-bold mb-0"><?= e($a['name']) ?></h6>
                    <span class="text-muted small"><?= e($a['email']) ?></span><br>
                    <span class="badge bg-light text-dark border mt-1"><i class="bi bi-star me-1"></i><?= e($a['expertise']) ?></span>
                </div>
            </div>
            <div class="row g-2 text-center mt-3">
                <div class="col-4">
                    <div class="p-2 rounded-3" style="background:#EFF6FF;">
                        <div class="fw-bold" style="color:#3B82F6; font-size:1.1rem;"><?= $a['active_count'] ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Active</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3" style="background:#ECFDF5;">
                        <div class="fw-bold" style="color:#10B981; font-size:1.1rem;"><?= $a['resolved_count'] ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Resolved</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3" style="background:#F5F3FF;">
                        <div class="fw-bold" style="color:#8B5CF6; font-size:1.1rem;"><?= $a['avg_hours'] ? $a['avg_hours'] . 'h' : '—' ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Avg Time</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create Agent Modal -->
<div class="modal fade" id="createAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="create_agent">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Temporary Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                    <div class="form-text">Share this password with the agent so they can log in.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Expertise</label>
                    <select name="expertise" class="form-select" required>
                        <option value="">Select expertise...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Agent</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
