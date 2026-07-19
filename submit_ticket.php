<?php
/**
 * Customer: Submit Ticket
 */
$pageTitle = 'Submit Ticket — NexusDesk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Only customers can access
requireLogin();
$user = getCurrentUser();
if ($user['role'] !== 'customer') { header('Location: dashboard.php'); exit; }

$success = false;
$createdTicket = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = trim($_POST['priority'] ?? 'Medium');

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (!in_array($priority, ['Low','Medium','High','Urgent'])) $errors[] = 'Invalid priority.';

    if (empty($errors)) {
        try {
            $pdo = getDbConnection();

            // AI categorization
            $category = 'Uncategorized';
            $sentiment = 'Neutral';
            $aiReply = '';

            if (file_exists(__DIR__ . '/config/gemini.php')) {
                require_once __DIR__ . '/config/gemini.php';
                $aiResult = analyzeTicket($title, $description);
                $category = $aiResult['category'] ?? 'Uncategorized';
                $sentiment = $aiResult['sentiment'] ?? 'Neutral';
                $aiReply = $aiResult['ai_suggested_reply'] ?? '';
            }

            // Find an expert agent for this category
            $agentId = null;
            $agentEmail = null;
            $agentName = null;
            if ($category !== 'Uncategorized') {
                $agentStmt = $pdo->prepare("SELECT id, email, name FROM agents WHERE expertise = ? ORDER BY RAND() LIMIT 1");
                $agentStmt->execute([$category]);
                $expert = $agentStmt->fetch();
                if ($expert) {
                    $agentId = $expert['id'];
                    $agentEmail = $expert['email'];
                    $agentName = $expert['name'];
                }
            }

            $stmt = $pdo->prepare("INSERT INTO tickets (title, description, category, priority, status, sentiment, ai_suggested_reply, customer_name, customer_email, agent_id) VALUES (?, ?, ?, ?, 'New', ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $priority, $sentiment, $aiReply, $user['full_name'], $user['email'], $agentId]);
            $ticketId = $pdo->lastInsertId();

            // Send Email to Agent if assigned
            if ($agentId && $agentEmail) {
                require_once __DIR__ . '/config/mail.php';
                $subject = "New Ticket Assigned: #{$ticketId} - {$title}";
                $body = "
                    <h2>New Ticket Assignment</h2>
                    <p>Hi {$agentName},</p>
                    <p>A new ticket has been assigned to you based on your expertise in <b>{$category}</b>.</p>
                    <p><b>Ticket Details:</b></p>
                    <ul>
                        <li><b>Ticket ID:</b> #{$ticketId}</li>
                        <li><b>Customer:</b> {$user['full_name']} ({$user['email']})</li>
                        <li><b>Priority:</b> {$priority}</li>
                        <li><b>Sentiment:</b> {$sentiment}</li>
                    </ul>
                    <p><b>Description:</b></p>
                    <p>" . nl2br(htmlspecialchars($description)) . "</p>
                    <p><a href='http://localhost:8000/ticket_detail.php?id={$ticketId}'>Click here to view the ticket</a></p>
                ";
                sendSystemEmail($agentEmail, $subject, $body);
            }

            // Handle file upload
            if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . "/uploads/{$ticketId}";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['attachment']['name']));
                move_uploaded_file($_FILES['attachment']['tmp_name'], "{$uploadDir}/{$filename}");

                $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, filename, filepath) VALUES (?, ?, ?)")
                    ->execute([$ticketId, $filename, "uploads/{$ticketId}/{$filename}"]);
            }

            $success = true;
            $createdTicket = [
                'id' => $ticketId, 'title' => $title, 'category' => $category,
                'sentiment' => $sentiment, 'priority' => $priority
            ];
        } catch (Exception $ex) {
            $errors[] = 'Failed to create ticket: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <h4 class="fw-bold mb-1 animate-in">✉️ Submit a New Ticket</h4>
        <p class="text-muted mb-4 animate-in delay-1">Describe your issue and our AI will categorize it automatically.</p>

        <?php if ($success && $createdTicket): ?>
            <!-- Success State -->
            <div class="card-custom p-4 text-center animate-in">
                <div style="font-size:3rem" class="mb-3">🎉</div>
                <h5 class="fw-bold">Ticket Created Successfully!</h5>
                <p class="text-muted">Your ticket <strong>#<?= $createdTicket['id'] ?></strong> has been submitted.</p>

                <div class="d-flex justify-content-center gap-3 flex-wrap my-3">
                    <div class="stat-card px-4 py-3">
                        <div class="stat-label">Category</div>
                        <div class="fw-bold text-accent"><?= e($createdTicket['category']) ?></div>
                    </div>
                    <div class="stat-card px-4 py-3">
                        <div class="stat-label">Sentiment</div>
                        <div><?= sentimentBadge($createdTicket['sentiment']) ?></div>
                    </div>
                    <div class="stat-card px-4 py-3">
                        <div class="stat-label">Priority</div>
                        <div><?= priorityBadge($createdTicket['priority']) ?></div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="ticket_detail.php?id=<?= $createdTicket['id'] ?>" class="btn btn-accent">View Ticket</a>
                    <a href="submit_ticket.php" class="btn btn-outline-secondary">Submit Another</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-3 animate-in">
                    <?php foreach ($errors as $err): ?>
                        <div><i class="bi bi-exclamation-circle me-1"></i><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Ticket Form -->
            <div class="card-custom p-4 animate-in delay-2">
                <form method="POST" enctype="multipart/form-data" id="ticketForm">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Title</label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="Brief summary of your issue" required
                               value="<?= e($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"
                                  placeholder="Describe your issue in detail..." required><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="priority" class="form-label fw-semibold">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="Low" <?= ($_POST['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>🟢 Low</option>
                            <option value="Medium" <?= ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : '' ?>>🔵 Medium</option>
                            <option value="High" <?= ($_POST['priority'] ?? '') === 'High' ? 'selected' : '' ?>>🟠 High</option>
                            <option value="Urgent" <?= ($_POST['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>🔴 Urgent</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Attachment (optional)</label>
                        <div class="dropzone-area" id="dropzone" onclick="document.getElementById('attachment').click()">
                            <div class="dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                            <p class="mb-1 fw-semibold">Click or drag & drop a file</p>
                            <p class="text-muted small mb-0">Max 5MB — images, docs, PDFs</p>
                            <div id="filePreview" class="mt-2"></div>
                        </div>
                        <input type="file" id="attachment" name="attachment" class="d-none" accept="image/*,.pdf,.doc,.docx,.txt">
                    </div>

                    <button type="submit" class="btn btn-accent w-100 py-2" id="submitBtn">
                        <i class="bi bi-send me-2"></i>Submit Ticket
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
    // Drag and drop
    const dz = document.getElementById('dropzone');
    const fi = document.getElementById('attachment');
    const preview = document.getElementById('filePreview');

    if (dz) {
        ['dragenter','dragover'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.add('dragover'); }));
        ['dragleave','drop'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.remove('dragover'); }));
        dz.addEventListener('drop', ev => { fi.files = ev.dataTransfer.files; showFile(); });
        fi.addEventListener('change', showFile);
    }

    function showFile() {
        if (fi.files.length > 0) {
            const f = fi.files[0];
            const size = (f.size / 1024).toFixed(1);
            preview.innerHTML = `<div class="d-flex align-items-center gap-2 justify-content-center mt-2">
                <i class="bi bi-file-earmark text-accent"></i>
                <span class="fw-semibold small">${f.name}</span>
                <span class="text-muted small">(${size} KB)</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">&times;</button>
            </div>`;
        }
    }

    function clearFile() {
        fi.value = '';
        preview.innerHTML = '';
    }

    // Submit button loading
    document.getElementById('ticketForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        btn.disabled = true;
    });
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
