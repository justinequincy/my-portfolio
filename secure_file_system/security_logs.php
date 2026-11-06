<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: login.php');
    exit;
}

$logs = $pdo->query(
    'SELECT event, details, created_at FROM security_logs ORDER BY id DESC LIMIT 50'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f9; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 12px; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #172554; color: white; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="management.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="upload.php">Upload Files</a>
        <a href="view_files.php">View Files</a>
        <a href="logout.php">Logout</a>
    </div>

    <h1>Security Logs</h1>

    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Details</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= escape((string) ($log['event'] ?? '')) ?></td>
                    <td><?= escape((string) ($log['details'] ?? '')) ?></td>
                    <td><?= escape((string) ($log['created_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
