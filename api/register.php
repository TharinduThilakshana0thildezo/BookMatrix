<?php
// JSON registration endpoint for frontend SPA
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

// Basic validation
$email    = isset($_POST['user_email_address']) ? trim($_POST['user_email_address']) : '';
$password = isset($_POST['user_password']) ? trim($_POST['user_password']) : '';
$name     = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
$address  = isset($_POST['user_address']) ? trim($_POST['user_address']) : '';
$contact  = isset($_POST['user_contact_no']) ? trim($_POST['user_contact_no']) : '';

if ($email === '' || $password === '' || $name === '' || $address === '' || $contact === '') {
    json_response([
        'success' => false,
        'code' => 'validation',
        'message' => 'All fields are required.'
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'success' => false,
        'code' => 'email_format',
        'message' => 'Invalid email address.'
    ], 400);
}

// Check duplicate email
$query = 'SELECT 1 FROM lms_user WHERE user_email_address = :email LIMIT 1';
$statement = $connect->prepare($query);
$statement->execute([':email' => $email]);

if ($statement->rowCount() > 0) {
    json_response([
        'success' => false,
        'code' => 'duplicate',
        'message' => 'Email already registered.'
    ], 409);
}

// Handle optional profile image (more relaxed than original form)
$profileFileName = '';
if (!empty($_FILES['user_profile']['name'])) {
    $imgName = $_FILES['user_profile']['name'];
    $tmpName = $_FILES['user_profile']['tmp_name'];
    $imgExplode = explode('.', $imgName);
    $imgExt = strtolower(end($imgExplode));
    $allowed = ['jpeg', 'jpg', 'png'];

    if (in_array($imgExt, $allowed)) {
        $profileFileName = time() . '-' . rand() . '.' . $imgExt;
        @move_uploaded_file($tmpName, '../upload/' . $profileFileName);
    }
}

$userVerificationCode = md5(uniqid((string)mt_rand(), true));
$userUniqueId = 'U' . rand(10000000, 99999999);

$data = [
    ':user_name'               => $name,
    ':user_address'            => $address,
    ':user_contact_no'         => $contact,
    ':user_profile'            => $profileFileName !== '' ? $profileFileName : 'default.png',
    ':user_email_address'      => $email,
    ':user_password'           => $password,
    ':user_verificaton_code'   => $userVerificationCode,
    ':user_verification_status'=> 'Yes', // Mark verified for SPA flow
    ':user_unique_id'          => $userUniqueId,
    ':user_status'             => 'Enable',
    ':user_created_on'         => get_date_time($connect)
];

$insert = 'INSERT INTO lms_user 
    (user_name, user_address, user_contact_no, user_profile, user_email_address, user_password, user_verificaton_code, user_verification_status, user_unique_id, user_status, user_created_on)
    VALUES (:user_name, :user_address, :user_contact_no, :user_profile, :user_email_address, :user_password, :user_verificaton_code, :user_verification_status, :user_unique_id, :user_status, :user_created_on)';

try {
    $statement = $connect->prepare($insert);
    $statement->execute($data);
} catch (PDOException $e) {
    json_response([
        'success' => false,
        'code' => 'db_error',
        'message' => 'Registration failed due to a server error.'
    ], 500);
}

// Auto-login for SPA: set session user_id to user_unique_id
$_SESSION['user_id'] = $userUniqueId;

json_response([
    'success' => true,
    'code' => 'ok',
    'message' => 'Registration successful.',
    'data' => [
        'user_unique_id' => $userUniqueId,
        'user_name' => $name,
        'user_email_address' => $email,
        'user_contact_no' => $contact,
        'user_address' => $address,
        'user_profile' => $profileFileName !== '' ? $profileFileName : 'default.png'
    ]
]);
