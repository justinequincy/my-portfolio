<?php
require_once __DIR__ . '/config/auth.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Location: ' . (($_SESSION['role'] ?? '') === 'manager' ? 'management.php' : 'employee.php'));
exit;
