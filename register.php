<?php
require_once __DIR__ . '/config/session.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    redirectUserBasedOnRole();
}

// Get any errors from auth_handler
$errors = $_SESSION['auth_errors'] ?? [];
$formData = $_SESSION['auth_form_data'] ?? [];
unset($_SESSION['auth_errors'], $_SESSION['auth_form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — NexusDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        :root {
            --accent: #6366F1;
            --accent-hover: #5558E6;
            --accent-light: #EEF2FF;
            --bg-primary: #F8FAFF;
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            z-index: 0;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 400px; height: 400px;
            background: rgba(99, 102, 241, 0.12);
            top: -100px; left: -100px;
        }
        body::after {
            width: 350px; height: 350px;
            background: rgba(139, 92, 246, 0.1);
            bottom: -80px; right: -80px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(-5deg); }
        }

        .auth-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .auth-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(228, 232, 244, 0.6);
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-logo {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--accent), #8B5CF6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 20px;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        }

        .auth-title {
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            color: #1A1D2E;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            font-size: 0.9rem;
            color: #5A5F7A;
            text-align: center;
            margin-bottom: 28px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1A1D2E;
            margin-bottom: 6px;
        }

        .form-control {
            border: 1.5px solid #E4E8F4;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .input-group .form-control { border-right: none; }
        .input-group .input-group-text {
            background: #fff;
            border: 1.5px solid #E4E8F4;
            border-left: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            color: #8B90A8;
            transition: color 0.2s;
        }
        .input-group .input-group-text:hover { color: var(--accent); }

        .btn-primary {
            background: var(--accent);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.88rem;
            color: #5A5F7A;
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover { text-decoration: underline; }

        .alert {
            border-radius: 12px;
            font-size: 0.85rem;
            border: none;
            padding: 12px 16px;
        }

        .alert-danger {
            background: #FEF2F2;
            color: #DC2626;
        }

        .password-strength {
            height: 4px;
            border-radius: 4px;
            background: #E4E8F4;
            margin-top: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .password-strength__bar {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: all 0.3s ease;
        }

        .password-strength__bar.weak { width: 33%; background: #EF4444; }
        .password-strength__bar.medium { width: 66%; background: #F59E0B; }
        .password-strength__bar.strong { width: 100%; background: #10B981; }

        .password-hint {
            font-size: 0.75rem;
            color: #8B90A8;
            margin-top: 4px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: #8B90A8;
            font-size: 0.8rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E4E8F4;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="text-center mb-4">
                <img src="assets/logo.png" alt="NexusDesk Logo" height="60">
            </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join NexusDesk to get started</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="auth_handler.php" method="POST" id="registerForm" novalidate>
                <input type="hidden" name="action" value="register">

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="full_name" name="full_name"
                               placeholder="John Doe" required autocomplete="name"
                               value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                    </div>
                    <div class="invalid-feedback" id="nameError"></div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="you@example.com" required autocomplete="email"
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    </div>
                    <div class="invalid-feedback" id="emailError"></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Minimum 6 characters" required autocomplete="new-password">
                        <span class="input-group-text" id="togglePassword">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </span>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength__bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint" id="strengthText">Enter a password</div>
                    <div class="invalid-feedback" id="passwordError"></div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Re-enter your password" required autocomplete="new-password">
                        <span class="input-group-text" id="toggleConfirm">
                            <i class="bi bi-eye-slash" id="toggleConfirmIcon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback" id="confirmError"></div>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="divider">or</div>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
                <div class="mt-2 text-muted small">Crafted with ❤️ by <a href="https://skillnest-beige.vercel.app" target="_blank" rel="noopener noreferrer" class="fw-semibold">SKILLNEST</a></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function setupToggle(toggleId, inputId, iconId) {
            document.getElementById(toggleId).addEventListener('click', function() {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                }
            });
        }
        setupToggle('togglePassword', 'password', 'toggleIcon');
        setupToggle('toggleConfirm', 'confirm_password', 'toggleConfirmIcon');

        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            
            bar.className = 'password-strength__bar';

            if (val.length === 0) {
                text.textContent = 'Enter a password';
                return;
            }

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (score <= 2) {
                bar.classList.add('weak');
                text.textContent = 'Weak password';
                text.style.color = '#EF4444';
            } else if (score <= 3) {
                bar.classList.add('medium');
                text.textContent = 'Medium strength';
                text.style.color = '#F59E0B';
            } else {
                bar.classList.add('strong');
                text.textContent = 'Strong password';
                text.style.color = '#10B981';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let valid = true;
            const name = document.getElementById('full_name');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');

            // Reset
            [name, email, password, confirm].forEach(el => el.classList.remove('is-invalid'));

            if (!name.value.trim() || name.value.trim().length < 2) {
                name.classList.add('is-invalid');
                document.getElementById('nameError').textContent = 'Full name must be at least 2 characters.';
                valid = false;
            }

            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('is-invalid');
                document.getElementById('emailError').textContent = 'Please enter a valid email address.';
                valid = false;
            }

            if (!password.value || password.value.length < 6) {
                password.classList.add('is-invalid');
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
                valid = false;
            }

            if (password.value !== confirm.value) {
                confirm.classList.add('is-invalid');
                document.getElementById('confirmError').textContent = 'Passwords do not match.';
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            } else {
                document.getElementById('submitBtn').innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...';
                document.getElementById('submitBtn').disabled = true;
            }
        });
    </script>
</body>
</html>
