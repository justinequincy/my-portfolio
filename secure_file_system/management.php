```php
<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: login.php');
    exit;
}

$totalUsers = 0;
$totalManagers = 0;
$totalFiles = 0;

$totalUsersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee', 'user', 'staff', 'accounting')");
if ($totalUsersStmt) {
    $totalUsers = (int) $totalUsersStmt->fetchColumn();
}

$totalManagersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager'");
if ($totalManagersStmt) {
    $totalManagers = (int) $totalManagersStmt->fetchColumn();
}

$fileTableCheck = $pdo->query("SHOW TABLES LIKE 'files'");
if ($fileTableCheck && $fileTableCheck->fetchColumn()) {
    $fileCountStmt = $pdo->query('SELECT COUNT(*) FROM files');
    if ($fileCountStmt) {
        $totalFiles = (int) $fileCountStmt->fetchColumn();
    }
}

$hasUsernameColumn = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();
$usersSql = $hasUsernameColumn
    ? 'SELECT id, full_name, username, email, role FROM users ORDER BY id DESC'
    : 'SELECT id, full_name, email, role FROM users ORDER BY id DESC';
$usersResult = $pdo->query($usersSql);

$currentUserStmt = $pdo->prepare('SELECT full_name, email, role FROM users WHERE id = ? LIMIT 1');
$currentUserStmt->execute([$_SESSION['user_id']]);
$currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | Secure File System</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #172554;
            color: white;
            padding: 25px 15px;
        }
        .sidebar h2 {
            text-align: center;
            font-size: 21px;
            margin-bottom: 35px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .sidebar a:hover, .sidebar a.active { background: #1d4ed8; }
        .main-content {
            margin-left: 240px;
            padding: 25px;
        }
        .topbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .topbar h1 { margin: 0; color: #172554; font-size: 25px; }
        .welcome { color: #555; }
        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .card {
            padding: 25px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 5px solid #2563eb;
        }
        .card h3 {
            margin-top: 0;
            color: #555;
            font-size: 15px;
        }
        .card p {
            margin-bottom: 0;
            font-size: 30px;
            font-weight: bold;
            color: #172554;
        }
        .section {
            margin-top: 30px;
            padding: 25px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .section h2 { margin-top: 0; color: #172554; }
        .table-container { width: 100%; overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #eff6ff; color: #172554; }
        .role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .logout {
            background: #dc2626;
            color: white !important;
            text-align: center;
            margin-top: 35px;
        }
        .logout:hover { background: #b91c1c !important; }
        @media (max-width: 900px) {
            .sidebar { width: 190px; }
            .main-content { margin-left: 190px; }
            .cards { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
        @media (max-width: 600px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .sidebar h2 { margin-bottom: 15px; }
            .main-content { margin-left: 0; padding: 15px; }
            .cards { grid-template-columns: 1fr; }
            .topbar h1 { font-size: 21px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Secure File System</h2>
        <a href="management.php" class="active">Dashboard</a>
        <a href="reset_password.php">Change Password</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="upload.php">Upload Files</a>
        <a href="view_files.php">View Files</a>
        <a href="security_logs.php">Security Logs</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <main class="main-content">
        <div class="topbar">
            <h1>Manager Dashboard</h1>
            <div class="welcome">
                Welcome,
                <strong><?= escape($_SESSION['username'] ?? $_SESSION['full_name'] ?? 'Manager') ?></strong>
            </div>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Registered Users</h3>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="card">
                <h3>Manager Accounts</h3>
                <p><?= $totalManagers ?></p>
            </div>
            <div class="card">
                <h3>Stored Files</h3>
                <p><?= $totalFiles ?></p>
            </div>
        </div>

        <div class="section">
            <h2>Current Session</h2>
            <p><strong>Name:</strong> <?= escape((string) ($currentUser['full_name'] ?? $_SESSION['full_name'] ?? 'Manager')) ?></p>
            <p><strong>Email:</strong> <?= escape((string) ($currentUser['email'] ?? $_SESSION['email'] ?? '')) ?></p>
            <p><strong>Role:</strong> <?= escape((string) ($currentUser['role'] ?? $_SESSION['role'] ?? 'manager')) ?></p>
        </div>

        <div class="section">
            <h2>Registered Accounts</h2>
            <p>View the accounts registered in the system.</p>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usersResult && $usersResult->rowCount() > 0): ?>
                            <?php while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)): ?>
                                <?php
                                    $displayName = $user['username'] ?? $user['full_name'] ?? $user['email'] ?? '';
                                ?>
                                <tr>
                                    <td><?= (int) $user['id'] ?></td>
                                    <td><?= escape((string) $displayName) ?></td>
                                    <td><span class="role"><?= escape(ucfirst((string) ($user['role'] ?? ''))) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No registered accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>System Information</h2>
            <p>You are logged in to the Secure File Management System.</p>
            <p>Use the navigation menu to manage accounts, access files, and review security logs.</p>
            <p><strong>Current Role:</strong> Manager</p>
        </div>
    </main>
</body>
</html>
```
