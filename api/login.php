<?php
// JSON login endpoint for frontend SPA
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$email = isset($_POST['user_email_address']) ? trim($_POST['user_email_address']) : '';
$password = isset($_POST['user_password']) ? trim($_POST['user_password']) : '';

if ($email === '' || $password === '') {
    json_response([
        'success' => false,
        'code' => 'validation',
        'message' => 'Email and password are required.'
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'success' => false,
        'code' => 'email_format',
        'message' => 'Invalid email address.'
    ], 400);
}

// Look up user by email
$query = "SELECT * FROM lms_user WHERE user_email_address = :email LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute([':email' => $email]);

if ($statement->rowCount() === 0) {
    json_response([
        'success' => false,
        'code' => 'email',
        'message' => 'Email not found.'
    ], 401);
}

$user = $statement->fetch(PDO::FETCH_ASSOC);

if ($user['user_status'] !== 'Enable') {
    json_response([
        'success' => false,
        'code' => 'disabled',
        'message' => 'Your account has been disabled. Contact the administrator.'
    ], 403);
}

// Plain-text password check (matches existing legacy implementation)
if ($user['user_password'] !== $password) {
    json_response([
        'success' => false,
        'code' => 'password',
        'message' => 'Password is incorrect.'
    ], 401);
}

// Optional: email verification gate. Uncomment if you require verification.
// if ($user['user_verification_status'] !== 'Yes') {
//     json_response([
//         'success' => false,
//         'code' => 'unverified',
//         'message' => 'Please verify your email address before logging in.'
//     ], 403);
// }

// Success: establish user session for PHP side
$_SESSION['user_id'] = $user['user_unique_id'];

json_response([
    'success' => true,
    'code' => 'ok',
    'message' => 'Login successful.',
    'data' => [
        'user_unique_id' => $user['user_unique_id'],
        'user_name' => $user['user_name'],
        'user_email_address' => $user['user_email_address'],
        'user_contact_no' => $user['user_contact_no'],
        'user_address' => $user['user_address'],
        'user_profile' => $user['user_profile']
    ]
]);
