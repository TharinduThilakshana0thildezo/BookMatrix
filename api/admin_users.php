<?php
session_start();

require_once __DIR__ . '/../database_connection.php';
require_once __DIR__ . '/../function.php';

header('Content-Type: application/json');

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!is_admin_login()) {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $stmt = $connect->query("SELECT user_id, user_unique_id, user_name, user_email_address, user_contact_no, user_address, user_status, user_created_on, user_updated_on FROM lms_user ORDER BY user_id DESC");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        json_response(['success' => true, 'data' => $users]);
    } catch (Throwable $e) {
        json_response(['success' => false, 'error' => 'Failed to load users', 'details' => $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? '';
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

    if ($action !== 'delete' || $userId <= 0) {
        json_response(['success' => false, 'error' => 'Invalid request'], 400);
    }

    try {
        // Look up the user's unique id so we can also clean up any owned books.
        $stmtLookup = $connect->prepare('SELECT user_unique_id FROM lms_user WHERE user_id = :id LIMIT 1');
        $stmtLookup->execute([':id' => $userId]);
        $user = $stmtLookup->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            json_response(['success' => false, 'error' => 'User not found'], 404);
        }
        $uniqueId = $user['user_unique_id'];

        // Delete any issues associated with this user.
        try {
            $stmtIssues = $connect->prepare('DELETE FROM lms_issue_book WHERE user_id = :uid');
            $stmtIssues->execute([':uid' => $uniqueId]);
        } catch (Throwable $e) {
            // Non-fatal; continue with user deletion.
        }

        // Delete any books owned by this user (per-user SPA ownership uses owner_user_id = user_unique_id).
        try {
            $stmtBooks = $connect->prepare('DELETE FROM lms_book WHERE owner_user_id = :uid');
            $stmtBooks->execute([':uid' => $uniqueId]);
        } catch (Throwable $e) {
            // Non-fatal; continue with user deletion.
        }

        // Finally, remove the user record itself.
        $stmtDelete = $connect->prepare('DELETE FROM lms_user WHERE user_id = :id');
        $stmtDelete->execute([':id' => $userId]);

        if ($stmtDelete->rowCount() === 0) {
            json_response(['success' => false, 'error' => 'User delete failed'], 500);
        }

        json_response(['success' => true]);
    } catch (Throwable $e) {
        json_response(['success' => false, 'error' => 'User delete failed', 'details' => $e->getMessage()], 500);
    }
}

json_response(['success' => false, 'error' => 'Method not allowed'], 405);
