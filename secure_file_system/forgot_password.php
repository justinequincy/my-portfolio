<?php
require_once __DIR__ . '/config/auth.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? '') === 'manager') ? 'management.php' : 'employee.php';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Password Help | Secure File System</title>
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
        p { color: #475569; line-height: 1.5; }
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
        .success { background: #dcfce7; color: #166534; }
        .link { display: block; text-align: center; margin-top: 20px; color: #1d4ed8; }
    </style>
</head>
<body>
<main class="box">
    <h1>Forgot your password?</h1>
    <p>Password reset links are not sent by email. Please contact your manager and ask them to set a temporary password from <strong>Manage Users</strong>. After signing in, change it from the <strong>Change Password</strong> page.</p>
    <p>If you are a manager, contact your system administrator for help recovering access.</p>
    <a class="link" href="login.php">Back to login</a>
</main>
</body>
</html>
