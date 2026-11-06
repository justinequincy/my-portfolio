<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$isManager = ($_SESSION['role'] ?? '') === 'manager';
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrfToken()) {
        $message = 'Invalid request. Please refresh and try again.';
    } else {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            $message = 'Please select a valid file to upload.';
        } else {
            $file = $_FILES['file'];
            $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError !== UPLOAD_ERR_OK) {
                $message = match ($uploadError) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected file is too large.',
                    UPLOAD_ERR_NO_FILE => 'Please select a file to upload.',
                    default => 'The file could not be uploaded. Please try again.'
                };
            } elseif (!is_string($file['name'] ?? null) || !is_string($file['tmp_name'] ?? null) || !is_int($file['size'] ?? null)) {
                $message = 'Please select a valid file to upload.';
            } else {
                $originalName = basename($file['name']);
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $temporaryPath = $file['tmp_name'];
                $mimeType = is_file($temporaryPath)
                    ? (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath)
                    : false;
                $allowedMimeTypes = [
                    'pdf' => ['application/pdf'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    'txt' => ['text/plain']
                ];

                if (
                    !isset($allowedMimeTypes[$extension])
                    || !is_string($mimeType)
                    || !in_array($mimeType, $allowedMimeTypes[$extension], true)
                ) {
                    $message = 'Only PDF, DOCX, and TXT files are allowed.';
                } else {
                    $uploadDir = __DIR__ . '/uploads';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
                        $message = 'Unable to prepare the upload directory.';
                    } else {
                        $safeName = bin2hex(random_bytes(8)) . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                        $destination = $uploadDir . '/' . $safeName;

                        if (!move_uploaded_file($temporaryPath, $destination)) {
                            $message = 'Unable to save the uploaded file.';
                        } else {
                            $stmt = $pdo->prepare(
                                'INSERT INTO files (uploaded_by, original_name, stored_name, file_size, mime_type, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())'
                            );
                            $stmt->execute([
                                $_SESSION['user_id'],
                                $originalName,
                                $safeName,
                                $file['size'],
                                $mimeType
                            ]);

                            $message = 'File uploaded successfully.';
                            log_security_event($pdo, 'file_upload', 'User ID ' . $_SESSION['user_id'] . ' uploaded ' . $originalName);
                        }
                    }
                }
            }
        }
    }
}

$files = $pdo->query(
    'SELECT id, original_name, stored_name, file_size, uploaded_at FROM files ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f9; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 12px; text-decoration: none; }
        .msg { padding: 10px; margin-bottom: 15px; background: #e8f5e9; color: #1b5e20; }
        form { background: white; padding: 20px; }
        input, button { padding: 8px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #172554; color: white; }
    </style>
</head>
<body>
    <div class="nav">
        <?php if ($isManager): ?>
        <a href="management.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="view_files.php">View Files</a>
        <a href="security_logs.php">Security Logs</a>
        <?php else: ?>
        <a href="employee.php">Dashboard</a>
        <a href="employee_user.php">Profile</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>

    <h1>Upload File</h1>

    <?php if ($message !== ''): ?>
        <div class="msg"><?= escape($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <input type="file" name="file" accept=".pdf,.docx,.txt,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain" required>
        <button type="submit">Upload</button>
    </form>

    <h2>Recent Uploads</h2>
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
                    <td><?= (int) $file['file_size'] ?> bytes</td>
                    <td><?= escape((string) ($file['uploaded_at'] ?? '')) ?></td>
                    <td><a href="view_files.php?download=<?= (int) $file['id'] ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
