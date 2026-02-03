<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_GET['id'])) {
    echo json_encode(["active" => [], "history" => []]);
    exit();
}

$student_id = $_GET['id'];

$activeSql = "
    SELECT t.id, i.name as item_name, i.asset_tag, t.borrow_date, 
    DATE_ADD(t.borrow_date, INTERVAL 3 DAY) as due_date
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    WHERE t.student_number = ? AND t.return_date IS NULL
    ORDER BY t.borrow_date DESC
";

$stmt = $conn->prepare($activeSql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$activeResult = $stmt->get_result();

$activeLoans = [];
while ($row = $activeResult->fetch_assoc()) {
    $activeLoans[] = $row;
}

$historySql = "
    SELECT t.id, i.name as item_name, t.borrow_date, t.return_date, t.status as `condition`
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    WHERE t.student_number = ? AND t.return_date IS NOT NULL
    ORDER BY t.return_date DESC
    LIMIT 20
";

$stmt2 = $conn->prepare($historySql);
$stmt2->bind_param("s", $student_id);
$stmt2->execute();
$historyResult = $stmt2->get_result();

$history = [];
while ($row = $historyResult->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    "active" => $activeLoans,
    "history" => $history
]);
?>