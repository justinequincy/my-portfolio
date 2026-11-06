<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? '') === 'manager') ? 'management.php' : 'employee.php';
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        $identifier = trim($_POST['username'] ?? $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $error = 'Please enter your username or email and password.';
        } else {
            $hasUsernameColumn = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();

            if ($hasUsernameColumn) {
                $stmt = $pdo->prepare(
                    'SELECT id, full_name, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1'
                );
                $stmt->execute([$identifier, $identifier]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1'
                );
                $stmt->execute([$identifier]);
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'] ?? $user['full_name'] ?? $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['display_name'] = $_SESSION['full_name'] ?: $_SESSION['username'];

                $eventName = (($user['role'] ?? '') === 'manager') ? 'manager_login' : 'employee_login';
                log_security_event($pdo, $eventName, ucfirst(($user['role'] ?? 'user')) . ' logged in: ' . ($_SESSION['display_name'] ?? 'unknown'));

                header('Location: ' . (($user['role'] ?? '') === 'manager' ? 'management.php' : 'employee.php'));
                exit;
            }

            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | Secure File System</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 45%, #60a5fa 100%);
        }
        .login-container {
            background: rgba(255, 255, 255, 0.96);
            width: 380px;
            max-width: 92%;
            padding: 34px 30px 26px;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28);
            border: 1px solid rgba(255,255,255,0.35);
        }
        h2 {
            text-align: center;
            color: #1e3a8a;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .subtitle {
            text-align: center;
            color: #475569;
            margin-bottom: 25px;
            font-weight: 600;
        }
        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #1f2937;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.14);
        }
        .password-field {
            position: relative;
            margin-bottom: 18px;
        }
        .password-field input {
            margin-bottom: 0;
            padding-right: 48px;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 6px;
            transform: translateY(-50%);
            color: #475569;
            background: transparent;
            border: 0;
            border-radius: 6px;
            box-shadow: none;
            cursor: pointer;
        }
        .toggle-password:hover {
            transform: translateY(-50%);
            color: #1d4ed8;
            background: #eff6ff;
            box-shadow: none;
        }
        .toggle-password:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        button {
            width: 100%;
            padding: 13px 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #fecaca;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1f3c88;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
        .helper {
            text-align: center;
            margin-top: 18px;
            color: #475569;
        }
        .helper a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 700;
        }
        .helper a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Secure Login</h2>
    <p class="subtitle">Secure File Management System</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">

        <label for="username">Username or Email</label>
        <input type="text" id="username" name="username" placeholder="Enter your username or email" autocomplete="username" required>

        <label for="password">Password</label>
        <div class="password-field">
            <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
            <button type="button" class="toggle-password" id="toggle-password" aria-label="Show password" aria-pressed="false">
                <svg class="show-password-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="hide-password-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden>
                    <path d="m3 3 18 18"/>
                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3 3.8"/>
                    <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 4-.8"/>
                </svg>
            </button>
        </div>

        <button type="submit">Login</button>
    </form>

    <p class="helper"><a href="forgot_password.php">Forgot your password?</a></p>

    <p style="text-align:center; margin-top:18px;">
        New employee? <a href="register.php">Create account</a>
    </p>
    <a href="/secure_file_system/index.php" class="back-link">Back to Home</a>
</div>
<script>
    const passwordInput = document.getElementById('password');
    const togglePasswordButton = document.getElementById('toggle-password');
    const showPasswordIcon = togglePasswordButton.querySelector('.show-password-icon');
    const hidePasswordIcon = togglePasswordButton.querySelector('.hide-password-icon');

    togglePasswordButton.addEventListener('click', () => {
        const isPasswordVisible = passwordInput.type === 'password';
        passwordInput.type = isPasswordVisible ? 'text' : 'password';
        togglePasswordButton.setAttribute('aria-label', isPasswordVisible ? 'Hide password' : 'Show password');
        togglePasswordButton.setAttribute('aria-pressed', String(isPasswordVisible));
        showPasswordIcon.hidden = isPasswordVisible;
        hidePasswordIcon.hidden = !isPasswordVisible;
    });
</script>
</body>
</html>
