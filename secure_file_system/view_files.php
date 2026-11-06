<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/antivirus.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['download'])) {
    if (!in_array($_SESSION['role'] ?? '', ['manager', 'employee'], true)) {
        http_response_code(403);
        exit('You are not authorized to download files.');
    }

    $fileId = (int) $_GET['download'];
    $stmt = $pdo->prepare('SELECT original_name, stored_name, mime_type FROM files WHERE id = ? LIMIT 1');
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $uploadDirectory = realpath(__DIR__ . '/uploads');
        $path = $uploadDirectory !== false
            ? realpath($uploadDirectory . DIRECTORY_SEPARATOR . basename($file['stored_name']))
            : false;

        if (
            $path !== false
            && is_file($path)
            && str_starts_with($path, $uploadDirectory . DIRECTORY_SEPARATOR)
        ) {
            $scanResult = scan_file_with_clamav($path);
            if ($scanResult['status'] === 'clean') {
                header('X-Content-Type-Options: nosniff');
                header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
                header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
                readfile($path);
                exit;
            }

            if ($scanResult['status'] === 'infected') {
                log_security_event(
                    $pdo,
                    'malware_detected',
                    'Blocked download of file ID ' . $fileId . ' for user ID ' . $_SESSION['user_id']
                );
                http_response_code(403);
                exit('Download blocked: malware was detected in this file.');
            }

            error_log('ClamAV scan failed for file ID ' . $fileId . ': ' . $scanResult['message']);
            http_response_code(503);
            exit('File scanning is unavailable or failed. The download has been blocked.');
        }
    }

    http_response_code(404);
    exit('File not found.');
}

if (($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: employee.php');
    exit;
}

$files = $pdo->query(
    'SELECT id, original_name, file_size, uploaded_at FROM files ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Files</title>
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
        <a href="security_logs.php">Security Logs</a>
        <a href="logout.php">Logout</a>
    </div>

    <h1>Uploaded Files</h1>

    <table>
        <thead>
            <tr>
                <th>File</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= escape((string) ($file['original_name'] ?? '')) ?></td>
                    <td><?= (int) ($file['file_size'] ?? 0) ?> bytes</td>
                    <td><?= escape((string) ($file['uploaded_at'] ?? '')) ?></td>
                    <td><a href="view_files.php?download=<?= (int) $file['id'] ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
