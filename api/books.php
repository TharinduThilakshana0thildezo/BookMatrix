<?php
// JSON facade for books
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($method === 'GET') {
    // Global book list (original behavior) used by admin/legacy pages.
    $query = "SELECT book_id, book_name, book_author, book_category, book_isbn_number, book_no_of_copy, book_status FROM lms_book ORDER BY book_id DESC";
    $statement = $connect->prepare($query);
    $statement->execute();
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function ($row) {
        $status = 'disabled';
        if ($row['book_status'] === 'Enable') {
            $status = ((int)$row['book_no_of_copy'] > 0) ? 'available' : 'issued';
        }
        return [
            'id' => (int)$row['book_id'],
            'title' => $row['book_name'],
            'author' => $row['book_author'],
            'category' => $row['book_category'],
            'isbn' => $row['book_isbn_number'],
            'copies' => (int)$row['book_no_of_copy'],
            'status' => $status
        ];
    }, $rows);

    json_response(['data' => $data]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id'])) {
        json_response(['error' => 'Missing book id'], 400);
    }
    // Only allow authenticated users to delete, and scope deletion to
    // books they own (owner_user_id matches their session id).
    if (!is_user_login()) {
        json_response(['error' => 'Not authenticated'], 401);
    }

    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id) {
        json_response(['error' => 'Session missing'], 401);
    }

    $data = [
        ':book_id' => $input['id'],
        ':owner_user_id' => $current_user_id
    ];

    // Permanently remove the book row for this user.
    $query = "DELETE FROM lms_book WHERE book_id = :book_id AND owner_user_id = :owner_user_id";
    $statement = $connect->prepare($query);
    $statement->execute($data);

    if ($statement->rowCount() === 0) {
        json_response(['error' => 'Book not found or not owned by user'], 404);
    }

    json_response(['ok' => true]);
}

json_response(['error' => 'Method not allowed'], 405);
