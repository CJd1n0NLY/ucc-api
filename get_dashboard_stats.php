<?php
include 'db.php';

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$role = isset($_GET['role']) ? $_GET['role'] : '';

if (empty($branch_id) && $role !== 'super_admin') {
    echo json_encode(["borrowed_today" => 0, "active_users" => 0, "overdue_items" => 0]);
    exit;
}

if ($role === 'super_admin' && empty($branch_id)) {
    $todayStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DATE(borrow_date) = CURDATE()");
    $todayStmt->execute();
    $today_count = $todayStmt->get_result()->fetch_assoc()['count'];

    $activeStmt = $conn->prepare("SELECT COUNT(DISTINCT student_number) as count FROM transactions WHERE return_date IS NULL");
    $activeStmt->execute();
    $active_count = $activeStmt->get_result()->fetch_assoc()['count'];

    $overdueStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL AND borrow_date < NOW() - INTERVAL 1 DAY");
    $overdueStmt->execute();
    $overdue_count = $overdueStmt->get_result()->fetch_assoc()['count'];

} else {
    $todayStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DATE(borrow_date) = CURDATE() AND branch_id = ?");
    $todayStmt->bind_param("i", $branch_id);
    $todayStmt->execute();
    $today_count = $todayStmt->get_result()->fetch_assoc()['count'];

    $activeStmt = $conn->prepare("SELECT COUNT(DISTINCT student_number) as count FROM transactions WHERE return_date IS NULL AND branch_id = ?");
    $activeStmt->bind_param("i", $branch_id);
    $activeStmt->execute();
    $active_count = $activeStmt->get_result()->fetch_assoc()['count'];

    $overdueStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL AND borrow_date < NOW() - INTERVAL 1 DAY AND branch_id = ?");
    $overdueStmt->bind_param("i", $branch_id);
    $overdueStmt->execute();
    $overdue_count = $overdueStmt->get_result()->fetch_assoc()['count'];
}

echo json_encode([
    "borrowed_today" => $today_count,
    "active_users" => $active_count,
    "overdue_items" => $overdue_count
]);
?>