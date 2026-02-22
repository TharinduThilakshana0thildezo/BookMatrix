<?php
session_start();

require_once __DIR__ . '/../database_connection.php';
require_once __DIR__ . '/../function.php';

header('Content-Type: application/json');

if (!is_admin_login()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Ensure owner_user_id exists for per-user books.
    try {
        $connect->query("SELECT owner_user_id FROM lms_book LIMIT 1");
    } catch (PDOException $e) {
        try {
            $connect->exec("ALTER TABLE lms_book ADD owner_user_id VARCHAR(50) NULL DEFAULT NULL");
        } catch (PDOException $inner) {
            // Non-fatal; continue without owner_user_id if the column cannot be added.
        }
    }

    $totals = [
        'books' => 0,
        'users' => 0,
        'issues' => 0,
        'issues_active' => 0,
    ];
    $realBookCount = (int)$connect->query('SELECT COUNT(*) FROM lms_book')->fetchColumn();
    // Display a premium-looking global total by adding a large base.
    // The admin dashboard will render this as "86542+" style.
    $totals['books'] = 86542 + $realBookCount;
    $totals['users'] = (int)$connect->query('SELECT COUNT(*) FROM lms_user')->fetchColumn();
    $totals['issues'] = (int)$connect->query('SELECT COUNT(*) FROM lms_issue_book')->fetchColumn();
    $totals['issues_active'] = (int)$connect->query("SELECT COUNT(*) FROM lms_issue_book WHERE book_issue_status = 'Issue'")->fetchColumn();

    $recentBooksStmt = $connect->query("SELECT book_id, book_name, book_author, book_category, owner_user_id, book_isbn_number AS book_isbn FROM lms_book ORDER BY book_id DESC LIMIT 6");
    $recent_books = $recentBooksStmt ? $recentBooksStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $recentUsersStmt = $connect->query("SELECT user_id, user_name, user_email_address, user_contact_no FROM lms_user ORDER BY user_id DESC LIMIT 6");
    $recent_users = $recentUsersStmt ? $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $latestIssuesStmt = $connect->query("SELECT issue_book_id, user_id, book_id AS book_isbn, book_issue_status, issue_date_time AS book_issue_date FROM lms_issue_book ORDER BY issue_book_id DESC LIMIT 6");
    $recent_issues = $latestIssuesStmt ? $latestIssuesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // All books with owner info for management view
    $booksStmt = $connect->query("SELECT b.book_id, b.book_name, b.book_author, b.book_category, b.book_isbn_number AS isbn, b.book_no_of_copy, b.owner_user_id, u.user_name
        FROM lms_book b
        LEFT JOIN lms_user u ON b.owner_user_id = u.user_unique_id
        ORDER BY b.book_id DESC");
    $books = $booksStmt ? $booksStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => $totals,
            'recent_books' => $recent_books,
            'recent_users' => $recent_users,
            'recent_issues' => $recent_issues,
            'books' => $books,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load admin overview', 'details' => $e->getMessage()]);
}
