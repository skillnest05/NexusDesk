<?php
/**
 * Auth Handler — Processes login and registration form submissions.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ---- REGISTER ----
if ($action === 'register') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    // Validation
    $errors = [];

    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDbConnection();

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists. Please sign in instead.';
            } else {
                // Hash password and insert user
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare('
                    INSERT INTO users (full_name, email, password, role)
                    VALUES (?, ?, ?, \'customer\')
                ');
                $stmt->execute([$fullName, $email, $hashedPassword]);

                // Auto-login after registration
                $userId = $pdo->lastInsertId();
                setUserSession([
                    'id'        => $userId,
                    'full_name' => $fullName,
                    'email'     => $email,
                    'role'      => 'customer',
                ]);

                redirectUserBasedOnRole();
            }
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong. Please try again.';
            error_log('Registration error: ' . $e->getMessage());
        }
    }

    // If there were errors, redirect back with errors
    $_SESSION['auth_errors'] = $errors;
    $_SESSION['auth_form_data'] = [
        'full_name' => $fullName,
        'email'     => $email,
    ];
    header('Location: register.php');
    exit;
}

// ---- LOGIN ----
if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $errors = [];

    if (empty($email)) {
        $errors[] = 'Email is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDbConnection();

            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // No account found
                $errors[] = 'No account found with this email. Please sign up first.';
                $_SESSION['auth_show_signup'] = true;
            } elseif (!password_verify($password, $user['password'])) {
                // Wrong password
                $errors[] = 'Incorrect password. Please try again.';
            } elseif ($user['role'] === 'suspended_agent') {
                $errors[] = 'You have been removed as an agent. In order to work again as an agent, please contact the admin manually.';
            } else {
                // Success — log in
                setUserSession($user);
                redirectUserBasedOnRole();
            }
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }

    // Redirect back with errors
    $_SESSION['auth_errors'] = $errors;
    $_SESSION['auth_form_data'] = ['email' => $email];
    header('Location: login.php');
    exit;
}

// ---- LOGOUT ----
if ($action === 'logout') {
    destroyUserSession();
    header('Location: login.php');
    exit;
}

// Default: redirect to login
header('Location: login.php');
exit;
