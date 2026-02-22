<?php
// JSON facade for current user profile
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!is_user_login()) {
    json_response(['error' => 'Not authenticated'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$user_unique_id = $_SESSION['user_id'] ?? null;

if (!$user_unique_id) {
    json_response(['error' => 'Session missing'], 401);
}

$query = "SELECT user_unique_id, user_name, user_email_address, user_contact_no, user_address, user_profile FROM lms_user WHERE user_unique_id = :uid LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute([':uid' => $user_unique_id]);
$user = $statement->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(['error' => 'User not found'], 404);
}

// Normalize payload keys for frontend
json_response([
    'data' => [
        'user_unique_id' => $user['user_unique_id'],
        'user_name' => $user['user_name'],
        'user_email_address' => $user['user_email_address'],
        'user_contact_no' => $user['user_contact_no'],
        'user_address' => $user['user_address'],
        'user_profile' => $user['user_profile'],
    ]
]);
