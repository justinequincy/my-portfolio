<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: login.php');
    exit;
}

$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrfToken()) {
        $message = 'Invalid request. Please refresh and try again.';
    } else {
        if (!empty($_POST['create_user'])) {
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = 'employee';

            if ($fullName === '' || strlen($fullName) > 120) {
                $message = 'Enter a valid full name for the employee.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $message = 'Enter a valid employee email address.';
            } elseif (strlen($password) < 8 || strlen($password) > 72) {
                $message = 'Employee password must be between 8 and 72 characters.';
            } else {
                $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $existing->execute([$email]);

                if ($existing->fetch()) {
                    $message = 'That email is already in use.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                    $message = 'Employee account created successfully.';
                    log_security_event($pdo, 'employee_created', 'Manager created employee account for ' . $email);
                }
            }
        } elseif (($_POST['action'] ?? '') === 'reset_employee_password') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? null;
            $confirmPassword = $_POST['confirm_password'] ?? null;

            if ($userId < 1) {
                $message = 'Select a valid employee account.';
            } elseif (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
                $message = 'Employee password must be between 8 and 72 characters.';
            } elseif (!is_string($confirmPassword) || $password !== $confirmPassword) {
                $message = 'The passwords do not match.';
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare(
                        "SELECT id FROM users WHERE id = ? AND role = 'employee' FOR UPDATE"
                    );
                    $stmt->execute([$userId]);

                    if (!$stmt->fetch()) {
                        $pdo->rollBack();
                        $message = 'The selected employee account was not found.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);

                        $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
                        $stmt->execute([$userId]);
                        log_security_event(
                            $pdo,
                            'employee_password_reset_by_manager',
                            'Manager reset password for employee user ID ' . $userId
                        );
                        $pdo->commit();
                        $message = 'Employee password updated. Give the new password to the employee securely.';
                    }
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }
            }
        } else {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? '';

            if ($userId > 0 && in_array($role, ['employee', 'manager'], true)) {
                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                $stmt->execute([$role, $userId]);
                $message = 'User role updated successfully.';
                log_security_event($pdo, 'role_update', 'User ID ' . $userId . ' set to role ' . $role);
            }
        }
    }
}

$users = $pdo->query(
    'SELECT id, full_name, email, role, created_at FROM users ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f9; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #172554; color: white; }
        form { display: inline; }
        select, button { padding: 8px; }
        .msg { padding: 10px; margin-bottom: 15px; background: #e8f5e9; color: #1b5e20; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 12px; text-decoration: none; }
        .reset-form { display: grid; gap: 6px; min-width: 190px; }
        .reset-form input, .reset-form button { padding: 7px; }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="management.php">Dashboard</a>
        <a href="upload.php">Upload Files</a>
        <a href="view_files.php">View Files</a>
        <a href="security_logs.php">Security Logs</a>
        <a href="logout.php">Logout</a>
    </div>

    <h1>Manage Users</h1>

    <?php if ($message !== ''): ?>
        <div class="msg"><?= escape($message) ?></div>
    <?php endif; ?>

    <div style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #ddd;">
        <h2>Create Employee Account</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
            <input type="hidden" name="create_user" value="1">
            <p>
                <label>Full Name</label><br>
                <input type="text" name="full_name" maxlength="120" required>
            </p>
            <p>
                <label>Email</label><br>
                <input type="email" name="email" maxlength="190" required>
            </p>
            <p>
                <label>Password</label><br>
                <input type="password" name="password" minlength="8" maxlength="72" required>
            </p>
            <button type="submit">Create Employee</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Reset Employee Password</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int) $user['id'] ?></td>
                    <td><?= escape((string) ($user['full_name'] ?? '')) ?></td>
                    <td><?= escape((string) ($user['email'] ?? '')) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <select name="role">
                                <option value="employee" <?= (($user['role'] ?? '') === 'employee') ? 'selected' : '' ?>>Employee</option>
                                <option value="manager" <?= (($user['role'] ?? '') === 'manager') ? 'selected' : '' ?>>Manager</option>
                            </select>
                            <button type="submit">Save</button>
                        </form>
                    </td>
                    <td><?= escape((string) ($user['created_at'] ?? '')) ?></td>
                    <td>
                        <?php if (($user['role'] ?? '') === 'employee'): ?>
                            <form method="post" class="reset-form">
                                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                <input type="hidden" name="action" value="reset_employee_password">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                <label>
                                    <span class="sr-only">New password for <?= escape((string) $user['full_name']) ?></span>
                                    <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="New password" required>
                                </label>
                                <label>
                                    <span class="sr-only">Confirm password for <?= escape((string) $user['full_name']) ?></span>
                                    <input type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Confirm password" required>
                                </label>
                                <button type="submit">Set Password</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
