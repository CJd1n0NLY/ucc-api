<?php
include 'db.php';
include 'auto_update_status.php';

$today = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(borrow_date) = CURDATE()");
$today_count = $today->fetch_assoc()['count'];

$active = $conn->query("SELECT COUNT(DISTINCT student_number) as count FROM transactions WHERE return_date IS NULL");
$active_count = $active->fetch_assoc()['count'];

$overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL AND borrow_date < NOW() - INTERVAL 1 DAY");
$overdue_count = $overdue->fetch_assoc()['count'];

echo json_encode([
    "borrowed_today" => $today_count,
    "active_users" => $active_count,
    "overdue_items" => $overdue_count
]);
?>