<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
$changePasswordMode = !empty($_SESSION['user_id']);
$currentUser = null;

if ($changePasswordMode) {
    $stmt = $pdo->prepare('SELECT password_hash, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser || !in_array($currentUser['role'], ['employee', 'manager'], true)) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

$token = $changePasswordMode
    ? ''
    : ($requestMethod === 'POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? ''));
$tokenIsValidFormat = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$tokenRow = null;

if ($tokenIsValidFormat) {
    $stmt = $pdo->prepare(
        'SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = '';
$success = false;

if ($requestMethod === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif ($changePasswordMode) {
        $currentPassword = $_POST['current_password'] ?? null;
        if (
            !is_string($currentPassword)
            || !password_verify($currentPassword, (string) $currentUser['password_hash'])
        ) {
            $error = 'Your current password is incorrect.';
        }
    } elseif (!$tokenRow) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    }

    if ($error === '') {
        $password = $_POST['password'] ?? null;
        $confirmPassword = $_POST['confirm_password'] ?? null;

        if (
            !is_string($password)
            || strlen($password) < 8
            || strlen($password) > 72
        ) {
            $error = 'Password must be between 8 and 72 characters.';
        } elseif (!is_string($confirmPassword) || $password !== $confirmPassword) {
            $error = 'The passwords do not match.';
        } else {
            $pdo->beginTransaction();
            try {
                if ($changePasswordMode) {
                    $userId = (int) $_SESSION['user_id'];
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);

                    $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
                    $stmt->execute([$userId]);
                    log_security_event($pdo, 'password_changed', 'Password changed by user ID ' . $userId);
                    $pdo->commit();
                    $success = true;
                } else {
                    $stmt = $pdo->prepare(
                        'SELECT id FROM password_reset_tokens
                         WHERE id = ? AND token_hash = ? AND expires_at > NOW()
                         FOR UPDATE'
                    );
                    $stmt->execute([(int) $tokenRow['id'], hash('sha256', $token)]);
                    $lockedToken = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$lockedToken) {
                        $pdo->rollBack();
                        $error = 'This reset link is invalid or has expired. Request a new one.';
                    } else {
                        $userId = (int) $tokenRow['user_id'];
                        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);

                        $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
                        $stmt->execute([$userId]);
                        log_security_event($pdo, 'password_reset_completed', 'Password reset for user ID ' . $userId);
                        $pdo->commit();
                        $success = true;
                    }
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Reset Password | Secure File System</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 45%, #60a5fa 100%);
        }
        .box {
            background: rgba(255, 255, 255, 0.96);
            width: 420px;
            max-width: 92%;
            padding: 34px 30px 26px;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28);
        }
        h1 { text-align: center; color: #1e3a8a; font-size: 1.8rem; }
        label { display: block; margin: 20px 0 7px; font-weight: bold; color: #1f2937; }
        input {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
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
        }
        .notice { padding: 10px; border-radius: 8px; text-align: center; }
        .error { background: #fee2e2; color: #991b1b; }
        .link { display: block; text-align: center; margin-top: 20px; color: #1d4ed8; }
    </style>
</head>
<body>
<main class="box">
    <h1><?= $changePasswordMode ? 'Change your password' : 'Reset your password' ?></h1>

    <?php if ($success): ?>
        <p class="notice">Your password has been updated successfully.</p>
        <a class="link" href="<?= $changePasswordMode && $currentUser['role'] === 'manager' ? 'management.php' : ($changePasswordMode ? 'employee.php' : 'login.php') ?>"><?= $changePasswordMode ? 'Return to dashboard' : 'Go to login' ?></a>
    <?php elseif (!$changePasswordMode && !$tokenRow): ?>
        <p class="notice error"><?= escape($error !== '' ? $error : 'This reset link is invalid or has expired. Request a new one.') ?></p>
        <a class="link" href="forgot_password.php">Request a new reset link</a>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <div class="notice error"><?= escape($error) ?></div>
        <?php endif; ?>
        <form method="post" action="reset_password.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
            <?php if ($changePasswordMode): ?>
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            <?php else: ?>
                <input type="hidden" name="token" value="<?= escape($token) ?>">
            <?php endif; ?>
            <label for="password">New password</label>
            <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
            <button type="submit">Save new password</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
