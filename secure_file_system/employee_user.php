<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') === 'manager') {
    header('Location: management.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT full_name, email, role FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee User Portal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        .wrap { max-width: 900px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.08); padding: 30px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        nav { margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap; }
        nav a { text-decoration: none; color: #172554; font-weight: bold; }
        .card { background: #eef6ff; padding: 18px; border-left: 5px solid #2563eb; border-radius: 8px; margin-bottom: 18px; }
        button { background: #dc2626; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>Employee Portal</h1>
            <div>Welcome, <strong><?= escape($user['full_name']) ?></strong></div>
        </div>

        <nav>
            <a href="employee.php">Dashboard</a>
            <a href="employee_user.php">Profile</a>
            <a href="reset_password.php">Change Password</a>
            <a href="upload.php">Upload Files</a>
        </nav>

        <div class="card">
            <h2>Account Details</h2>
            <p><strong>Name:</strong> <?= escape($user['full_name']) ?></p>
            <p><strong>Email:</strong> <?= escape($user['email']) ?></p>
            <p><strong>Role:</strong> <?= escape($user['role']) ?></p>
        </div>

        <div class="card">
            <h2>Available Features</h2>
            <ul>
                <li>Upload secure files</li>
                <li>Download shared documents</li>
                <li>Review system logs</li>
                <li>Maintain access control</li>
            </ul>
        </div>

        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
            <button type="submit">Log Out</button>
        </form>
    </div>
</body>
</html>
