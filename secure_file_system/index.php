<?php
require_once __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure File-Sharing System</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 45%, #60a5fa 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero {
            width: min(760px, 92%);
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.35);
            text-align: center;
        }
        h1 {
            margin: 0 0 16px;
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            letter-spacing: 0.04em;
        }
        p {
            margin: 0 0 28px;
            font-size: 1.08rem;
            color: #dbeafe;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 14px 26px;
            border-radius: 999px;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.25);
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .primary {
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            color: white;
        }
        .secondary {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            color: white;
        }
        .badge {
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(96, 165, 250, 0.22);
            border: 1px solid rgba(191, 219, 254, 0.45);
            color: #dbeafe;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="badge">Secure access</div>
        <h1>Secure File-Sharing System</h1>
        <p>A secure platform for company file sharing, team communication, and protected document handling.</p>

        <div class="actions">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['role'] ?? '') === 'manager'): ?>
                    <a class="btn primary" href="/secure_file_system/management.php">Open Dashboard</a>
                <?php else: ?>
                    <a class="btn primary" href="/secure_file_system/employee.php">Open Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn primary" href="/secure_file_system/login.php">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>