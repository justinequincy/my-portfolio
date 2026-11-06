<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? '') === 'manager') ? 'management.php' : 'employee.php';
    header('Location: ' . $redirect);
    exit;
}

$errors = [];
$fullName = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verifyCsrfToken()) {
        $errors[] = 'Invalid request. Refresh the page and try again.';
    }

    if ($fullName === '' || strlen($fullName) > 120) {
        $errors[] = 'Enter a valid full name up to 120 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Password must be between 8 and 72 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "employee")'
            );
            $stmt->execute([$fullName, $email, $hash]);
            log_security_event($pdo, 'user_registration', 'New employee account created for ' . $email);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Account Creation</title>
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
        .box {
            background: white;
            width: 440px;
            max-width: 92%;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #1f3c88; }
        label { display: block; margin-bottom: 7px; font-weight: bold; }
        input { width: 100%; padding: 11px; margin-bottom: 16px; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #1f3c88; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { background: #ffe5e5; color: #a00000; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .link { display: block; text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Create Employee Account</h2>

        <?php foreach ($errors as $error): ?>
            <div class="error"><?= escape($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">

            <label>Full name</label>
            <input type="text" name="full_name" maxlength="120" required value="<?= escape($fullName) ?>">

            <label>Email address</label>
            <input type="email" name="email" maxlength="190" required value="<?= escape($email) ?>">

            <label>Password</label>
            <input type="password" name="password" minlength="8" maxlength="72" required>

            <label>Confirm password</label>
            <input type="password" name="confirm_password" minlength="8" maxlength="72" required>

            <button type="submit">Create Account</button>
        </form>

        <a href="/secure_file_system/login.php" class="link">Already have an account? Log in</a>
    </div>
</body>
</html>
