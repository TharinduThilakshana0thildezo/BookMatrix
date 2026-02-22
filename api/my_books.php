<?php
// JSON facade for books issued to the currently logged-in user
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

$user_unique_id = $_SESSION['user_id'] ?? null;
if (!$user_unique_id) {
    json_response(['error' => 'Session missing'], 401);
}

$query = "
    SELECT 
        lms_issue_book.issue_book_id,
        lms_issue_book.book_id,
        lms_issue_book.issue_date_time,
        lms_issue_book.expected_return_date,
        lms_issue_book.return_date_time,
        lms_issue_book.book_fines,
        lms_issue_book.book_issue_status,
        lms_book.book_name,
        lms_book.book_isbn_number
    FROM lms_issue_book 
    INNER JOIN lms_book 
        ON lms_book.book_isbn_number = lms_issue_book.book_id 
    WHERE lms_issue_book.user_id = :uid 
    ORDER BY lms_issue_book.issue_book_id DESC
";

$statement = $connect->prepare($query);
$statement->execute([':uid' => $user_unique_id]);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function ($row) {
    return [
        'issue_id'   => (int)$row['issue_book_id'],
        'isbn'       => $row['book_isbn_number'],
        'title'      => $row['book_name'],
        'status'     => $row['book_issue_status'],
        'issued_at'  => $row['issue_date_time'],
        'due_at'     => $row['expected_return_date'],
        'returned_at'=> $row['return_date_time'],
        'fines'      => (float)$row['book_fines'],
    ];
}, $rows ?: []);

json_response(['data' => $data]);
