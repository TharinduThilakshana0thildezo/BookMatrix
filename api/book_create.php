<?php
// JSON endpoint to create a new book from the frontend
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

// Ensure books are always tied to the currently logged-in user so that
// each user only sees the books they have added from the frontend.
if (!is_user_login()) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    json_response(['success' => false, 'message' => 'Session missing'], 401);
}

// Make sure the lms_book table has an owner_user_id column. This keeps
// compatibility with the original schema and adds the column on demand.
function ensure_book_owner_column($connect) {
    try {
        $connect->query("SELECT owner_user_id FROM lms_book LIMIT 1");
    } catch (PDOException $e) {
        try {
            $connect->exec("ALTER TABLE lms_book ADD owner_user_id VARCHAR(50) NULL DEFAULT NULL");
        } catch (PDOException $inner) {
            // If this fails we still allow the request, but books will not
            // be filterable per-user.
        }
    }
}

ensure_book_owner_column($connect);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload'], 400);
}

$title    = trim($input['title'] ?? '');
$author   = trim($input['author'] ?? '');
$category = trim($input['category'] ?? '');
$location = trim($input['location'] ?? '');
$isbn     = trim($input['isbn'] ?? '');
$copies   = isset($input['copies']) ? (int)$input['copies'] : 0;

$errors = [];
if ($title === '') {
    $errors[] = 'Title is required';
}
if ($author === '') {
    $errors[] = 'Author is required';
}
if ($category === '') {
    $errors[] = 'Category is required';
}
if ($isbn === '') {
    $errors[] = 'ISBN is required';
}
if ($copies <= 0) {
    $errors[] = 'Number of copies must be greater than 0';
}

if ($errors) {
    json_response(['success' => false, 'message' => implode('\n', $errors)], 400);
}

// Fallbacks for optional fields
if ($location === '') {
    $location = 'General Rack';
}

try {
    $data = [
        ':book_category'       => $category,
        ':book_author'         => $author,
        ':book_location_rack'  => $location,
        ':book_name'           => $title,
        ':book_isbn_number'    => $isbn,
        ':book_no_of_copy'     => $copies,
        ':book_status'         => 'Enable',
        ':book_added_on'       => get_date_time($connect),
        ':owner_user_id'       => $current_user_id
    ];

    $sql = "
        INSERT INTO lms_book 
            (book_category, book_author, book_location_rack, book_name, book_isbn_number, book_no_of_copy, book_status, book_added_on, owner_user_id)
        VALUES
            (:book_category, :book_author, :book_location_rack, :book_name, :book_isbn_number, :book_no_of_copy, :book_status, :book_added_on, :owner_user_id)
    ";

    $stmt = $connect->prepare($sql);
    $stmt->execute($data);
    $newId = (int)$connect->lastInsertId();

    // Shape response like api/books.php
    $status = 'available';
    if ($data[':book_status'] !== 'Enable') {
        $status = 'disabled';
    } elseif ($copies <= 0) {
        $status = 'issued';
    }

    json_response([
        'success' => true,
        'data' => [
            'id'       => $newId,
            'title'    => $title,
            'author'   => $author,
            'category' => $category,
            'isbn'     => $isbn,
            'copies'   => $copies,
            'status'   => $status
        ]
    ], 201);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to add book'], 500);
}
