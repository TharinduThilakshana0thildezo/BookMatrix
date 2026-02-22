<?php
// JSON facade for books added by the currently logged-in user
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($method !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

if (!is_user_login()) {
    json_response(['error' => 'Not authenticated'], 401);
}

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    json_response(['error' => 'Session missing'], 401);
}

// Ensure the optional owner_user_id column exists. If this fails we
// still fall back to returning all books (legacy behavior).
try {
    $connect->query("SELECT owner_user_id FROM lms_book LIMIT 1");
} catch (PDOException $e) {
    try {
        $connect->exec("ALTER TABLE lms_book ADD owner_user_id VARCHAR(50) NULL DEFAULT NULL");
    } catch (PDOException $inner) {
        // Column could not be added; just return all books for now.
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
        }, $rows ?: []);

        json_response(['data' => $data]);
    }
}

// Normal per-user flow: only return books owned by the current user.
$query = "SELECT book_id, book_name, book_author, book_category, book_isbn_number, book_no_of_copy, book_status FROM lms_book WHERE owner_user_id = :uid ORDER BY book_id DESC";
$statement = $connect->prepare($query);
$statement->execute([':uid' => $current_user_id]);
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
}, $rows ?: []);

json_response(['data' => $data]);
