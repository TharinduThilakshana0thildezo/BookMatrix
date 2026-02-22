<?php
// Per-user dashboard stats
header('Content-Type: application/json');
require '../database_connection.php';
require '../function.php';

function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

if (!is_user_login()) {
    json_response(['error' => 'Not authenticated'], 401);
}

$user_unique_id = $_SESSION['user_id'] ?? null;
if (!$user_unique_id) {
    json_response(['error' => 'Session missing'], 401);
}

try {
    // Total books this user has added (via owner_user_id)
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM lms_book WHERE owner_user_id = :uid");
    $stmt->execute([':uid' => $user_unique_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
    $myBooks = (int)$row['total'];

    // Total issues for this user
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM lms_issue_book WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_unique_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
    $issuesTotal = (int)$row['total'];

    // Currently issued (not yet returned)
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM lms_issue_book WHERE user_id = :uid AND book_issue_status = 'Issue'");
    $stmt->execute([':uid' => $user_unique_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
    $issuesActive = (int)$row['total'];

    // Returned
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM lms_issue_book WHERE user_id = :uid AND book_issue_status = 'Return'");
    $stmt->execute([':uid' => $user_unique_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
    $issuesReturned = (int)$row['total'];

    // Circulation velocity: issues per month for last 6 months
    $stmt = $connect->prepare("SELECT DATE_FORMAT(issue_date_time, '%Y-%m') AS ym, COUNT(*) AS total FROM lms_issue_book WHERE user_id = :uid AND issue_date_time >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym ASC");
    $stmt->execute([':uid' => $user_unique_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Build a 6-month window including months with zero activity
    $labels = [];
    $data = [];
    $map = [];
    foreach ($rows as $r) {
        $map[$r['ym']] = (int)$r['total'];
    }

    $start = new DateTime('first day of -5 month');
    for ($i = 0; $i < 6; $i++) {
        $key = $start->format('Y-m');
        $labels[] = $start->format('M');
        $data[] = isset($map[$key]) ? $map[$key] : 0;
        $start->modify('+1 month');
    }

    // Recent activity: last 5 issue records for this user
    $stmt = $connect->prepare("SELECT lms_issue_book.issue_book_id, lms_issue_book.book_id, lms_issue_book.book_issue_status, lms_issue_book.issue_date_time, lms_issue_book.return_date_time, lms_book.book_name FROM lms_issue_book INNER JOIN lms_book ON lms_book.book_isbn_number = lms_issue_book.book_id WHERE lms_issue_book.user_id = :uid ORDER BY lms_issue_book.issue_date_time DESC LIMIT 5");
    $stmt->execute([':uid' => $user_unique_id]);
    $recentRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $recent = array_map(function ($row) {
        return [
            'book'   => $row['book_name'],
            'status' => $row['book_issue_status'],
            'time'   => $row['issue_date_time'],
        ];
    }, $recentRows);

    json_response([
        'data' => [
            'totals' => [
                'my_books' => $myBooks,
                'issues_total' => $issuesTotal,
                'issues_active' => $issuesActive,
                'issues_returned' => $issuesReturned,
            ],
            'velocity' => [
                'labels' => $labels,
                'data'   => $data,
            ],
            'recent' => $recent,
        ]
    ]);
} catch (Exception $e) {
    json_response(['error' => 'Failed to load stats'], 500);
}
