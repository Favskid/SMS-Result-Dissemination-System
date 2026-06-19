<?php

/**
 * admin/login.php
 * Authentication page for admins and staff.
 * Verifies credentials against the users table using password_verify().
 * On success, sets session variables and redirects to the dashboard.
 */

session_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Already logged in? Redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = post('password');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Fetch user record using a prepared statement (SQL-injection safe)
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];

            // Redirect to original destination or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? url('admin/dashboard.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e3a5f, #0f172a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, .97);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .45);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #1a2332, #243447);
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .login-header .icon-ring {
            width: 64px;
            height: 64px;
            background: rgba(59, 130, 246, .25);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .75rem;
        }

        .login-header h2 {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }

        .login-header p {
            color: #94a3b8;
            font-size: .82rem;
            margin: .3rem 0 0;
        }

        .login-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            font-size: .875rem;
            color: #374151;
        }

        .form-control {
            border-radius: 8px;
            border-color: #e2e8f0;
            padding: .65rem .9rem;
            font-size: .9rem;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
        }

        .btn-login {
            background: #3b82f6;
            border: none;
            border-radius: 8px;
            padding: .7rem;
            font-weight: 600;
            font-size: .9rem;
            color: #fff;
            width: 100%;
        }

        .btn-login:hover {
            background: #2563eb;
        }

        .hint-box {
            background: #f0f9ff;
            border-left: 3px solid #3b82f6;
            border-radius: 6px;
            padding: .75rem 1rem;
            font-size: .78rem;
            color: #1e40af;
        }

        .back-link {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .back-link a {
            color: #64748b;
            font-size: .8rem;
            text-decoration: none;
        }

        .back-link a:hover {
            color: #3b82f6;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <div class="icon-ring">
                <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
            </div>
            <h2>Staff / Admin Login</h2>
            <p><?= e(APP_INST) ?> — Result Management System</p>
        </div>

        <div class="login-body">
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i><?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('admin/login.php')) ?>" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-person text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="username" name="username"
                            placeholder="Enter username"
                            value="<?= e(post('username')) ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" id="password" name="password"
                            placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="text-muted small">Don't have an account?</span>
                <a href="<?= e(url('admin/register.php')) ?>" class="text-decoration-none fw-semibold small">Register here</a>
            </div>
        </div>

        <div class="back-link">
            <a href="<?= e(url('../index.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to Student Portal</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>