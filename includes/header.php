<?php
/**
 * Shared Header — Navigation bar with role-based links
 * Include this at the top of every protected page after session check.
 */
require_once __DIR__ . '/../config/session.php';
requireLogin();
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'NexusDesk' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top main-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <img src="assets/logo.png" alt="NexusDesk Logo" height="28" class="me-1">
            <span class="fw-bold">NexusDesk</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                    </a>
                </li>

                <?php if ($user['role'] === 'customer'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'submit_ticket.php' ? 'active' : '' ?>" href="submit_ticket.php">
                            <i class="bi bi-plus-circle me-1"></i>Submit Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'my_tickets.php' ? 'active' : '' ?>" href="my_tickets.php">
                            <i class="bi bi-ticket-perforated me-1"></i>My Tickets
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($user['role'] === 'agent'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'agent_queue.php' ? 'active' : '' ?>" href="agent_queue.php">
                            <i class="bi bi-inbox me-1"></i>My Queue
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin_tickets.php' ? 'active' : '' ?>" href="admin_tickets.php">
                            <i class="bi bi-ticket-perforated me-1"></i>All Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin_analytics.php' ? 'active' : '' ?>" href="admin_analytics.php">
                            <i class="bi bi-graph-up me-1"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin_agents.php' ? 'active' : '' ?>" href="admin_agents.php">
                            <i class="bi bi-people me-1"></i>Agents
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['full_name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($user['email']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="auth_handler.php?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
