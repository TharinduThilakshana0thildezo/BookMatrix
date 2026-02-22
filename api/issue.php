<?php
// JSON facade for issuing books
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$bookIsbn = trim($input['book_id'] ?? '');
$userId = trim($input['user_id'] ?? '');

if ($bookIsbn === '' || $userId === '') {
    json_response(['error' => 'book_id and user_id are required'], 400);
}

// Find book by ISBN
$query = "SELECT * FROM lms_book WHERE book_isbn_number = :isbn";
$stmt = $connect->prepare($query);
$stmt->execute([':isbn' => $bookIsbn]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    json_response(['error' => 'Book not found'], 404);
}

if ($book['book_status'] !== 'Enable' || (int)$book['book_no_of_copy'] <= 0) {
    json_response(['error' => 'Book not available'], 409);
}

// Find user by unique id
$query = "SELECT user_id, user_status FROM lms_user WHERE user_unique_id = :uid";
$stmt = $connect->prepare($query);
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(['error' => 'User not found'], 404);
}

if ($user['user_status'] !== 'Enable') {
    json_response(['error' => 'User is disabled'], 403);
}

// Check limits
$book_issue_limit = get_book_issue_limit_per_user($connect);
$total_book_issue = get_total_book_issue_per_user($connect, $userId);

if ($total_book_issue >= $book_issue_limit) {
    json_response(['error' => 'User reached issue limit'], 409);
}

$total_issue_days = get_total_book_issue_day($connect);
$today = get_date_time($connect);
$expected_return = date('Y-m-d H:i:s', strtotime($today . ' + ' . $total_issue_days . ' days'));

$data = [
    ':book_id' => $bookIsbn,
    ':user_id' => $userId,
    ':issue_date_time' => $today,
    ':expected_return_date' => $expected_return,
    ':return_date_time' => '',
    ':book_fines' => 0,
    ':book_issue_status' => 'Issue'
];

$insert = "INSERT INTO lms_issue_book (book_id, user_id, issue_date_time, expected_return_date, return_date_time, book_fines, book_issue_status) VALUES (:book_id, :user_id, :issue_date_time, :expected_return_date, :return_date_time, :book_fines, :book_issue_status)";
$stmt = $connect->prepare($insert);
$stmt->execute($data);

$update = "UPDATE lms_book SET book_no_of_copy = book_no_of_copy - 1, book_updated_on = :updated WHERE book_isbn_number = :isbn";
$connect->prepare($update)->execute([':updated' => $today, ':isbn' => $bookIsbn]);

json_response(['ok' => true, 'issue_id' => $connect->lastInsertId(), 'due' => $expected_return]);
