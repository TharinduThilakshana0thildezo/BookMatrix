<?php
session_start();

require_once __DIR__ . '/../database_connection.php';
require_once __DIR__ . '/../function.php';

header('Content-Type: application/json');

// Best-effort: ensure default admin exists without breaking login flow
try {
    $defaultEmail = 'admin@libraryos.com';
    $defaultHash = password_hash('Admin@12345!', PASSWORD_BCRYPT);

    // If the table exists and is readable, try to ensure there is at least
    // one admin row with the default credentials. Any schema/permission
    // problems will be logged but won't block the actual login request.
    $stmtCount = $connect->query('SELECT COUNT(*) FROM lms_admin');
    $rowCount = $stmtCount ? (int)$stmtCount->fetchColumn() : 0;

    if ($rowCount === 0) {
        // Legacy schema only has admin_email and admin_password, so do not
        // reference a non-existent admin_name column here.
        $seed = $connect->prepare('INSERT INTO lms_admin (admin_email, admin_password) VALUES (:email, :pass)');
        $seed->execute([
            ':email' => $defaultEmail,
            ':pass' => $defaultHash,
        ]);
    } else {
        $stmtAdmin = $connect->prepare('SELECT admin_id, admin_password FROM lms_admin WHERE admin_email = :email LIMIT 1');
        $stmtAdmin->execute([':email' => $defaultEmail]);
        $existing = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $seed = $connect->prepare('INSERT INTO lms_admin (admin_email, admin_password) VALUES (:email, :pass)');
            $seed->execute([
                ':email' => $defaultEmail,
                ':pass' => $defaultHash,
            ]);
        } elseif (!password_verify('Admin@12345!', $existing['admin_password'])) {
            $upd = $connect->prepare('UPDATE lms_admin SET admin_password = :pass WHERE admin_id = :id');
            $upd->execute([
                ':pass' => $defaultHash,
                ':id' => $existing['admin_id'],
            ]);
        }
    }
} catch (Throwable $e) {
    // Do not block login; just log for debugging if enabled on the server.
    if (function_exists('error_log')) {
        error_log('Admin bootstrap error: ' . $e->getMessage());
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

try {
    // Legacy lms_admin table has columns: admin_id, admin_email, admin_password
    $stmt = $connect->prepare('SELECT admin_id, admin_email, admin_password FROM lms_admin WHERE admin_email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['admin_password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_email'] = $admin['admin_email'];
    // Derive a display name for convenience; not stored in DB.
    $_SESSION['admin_name'] = 'Administrator';

    echo json_encode([
        'success' => true,
        'data' => [
            'admin_id' => (int)$admin['admin_id'],
            'admin_email' => $admin['admin_email'],
            'admin_name' => 'Administrator',
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage(),
        'details' => $e->getMessage()
    ]);
}
