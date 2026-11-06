<?php
$host = '127.0.0.1';
$dbname = 'secure_file_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check your configuration.');
}

$conn = $pdo;

function ensure_project_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uploaded_by INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_size BIGINT NOT NULL DEFAULT 0,
            mime_type VARCHAR(255) NOT NULL DEFAULT 'application/octet-stream',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX uploaded_by (uploaded_by),
            CONSTRAINT fk_files_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $uploadedByColumn = $pdo->query("SHOW COLUMNS FROM files LIKE 'uploaded_by'")->fetch();
    if ($uploadedByColumn === false) {
        $pdo->exec('ALTER TABLE files ADD COLUMN uploaded_by INT NULL AFTER id');

        $userIdColumn = $pdo->query("SHOW COLUMNS FROM files LIKE 'user_id'")->fetch();
        if ($userIdColumn !== false) {
            $pdo->exec(
                'UPDATE files f LEFT JOIN users u ON u.id = f.user_id
                 SET f.uploaded_by = u.id WHERE f.uploaded_by IS NULL AND u.id IS NOT NULL'
            );
        }
    }

    $uploadedByIndex = $pdo->query("SHOW INDEX FROM files WHERE Key_name = 'uploaded_by'")->fetch();
    if ($uploadedByIndex === false) {
        $pdo->exec('CREATE INDEX uploaded_by ON files (uploaded_by)');
    }

    $uploadedByForeignKey = $pdo->query(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'files'
           AND COLUMN_NAME = 'uploaded_by'
           AND REFERENCED_TABLE_NAME = 'users'
         LIMIT 1"
    )->fetch();
    if ($uploadedByForeignKey === false) {
        $pdo->exec(
            'ALTER TABLE files ADD CONSTRAINT fk_files_uploaded_by
             FOREIGN KEY (uploaded_by) REFERENCES users (id)'
        );
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(255) NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event (event)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_user (user_id),
            CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function log_security_event(PDO $pdo, string $event, string $details = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO security_logs (event, details) VALUES (?, ?)'
    );
    $stmt->execute([$event, $details]);
}

ensure_project_tables($pdo);
