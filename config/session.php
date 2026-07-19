<?php
/**
 * Session Management
 * Handles user sessions for authentication.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'],
        'email'     => $_SESSION['user_email'],
        'role'      => $_SESSION['user_role'],
    ];
}

/**
 * Set user session after successful login
 */
function setUserSession(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

/**
 * Destroy user session (logout)
 */
function destroyUserSession(): void {
    session_unset();
    session_destroy();
}

/**
 * Redirect if not logged in
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect user to their respective dashboard based on role
 */
function redirectUserBasedOnRole(): void {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    if ($user['role'] === 'admin') {
        header('Location: dashboard.php');
    } elseif ($user['role'] === 'agent') {
        header('Location: agent_queue.php');
    } else {
        header('Location: my_tickets.php');
    }
    exit;
}
