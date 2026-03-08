<?php
include 'db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Student ID required"]);
    exit();
}

$student_id = $_GET['id'];
$data = [
    "active" => [],
    "history" => [],
    "pending" => [] 
];

$activeQ = $conn->query("
    SELECT t.id as transaction_id, t.borrow_date, t.due_date, i.name as item_name, i.asset_tag 
    FROM transactions t 
    JOIN items i ON t.item_id = i.id 
    WHERE t.student_number = '$student_id' AND t.return_date IS NULL
");

while($row = $activeQ->fetch_assoc()) {
    $data["active"][] = $row;
}

$historyQ = $conn->query("
    SELECT t.return_date, t.status, i.name as item_name 
    FROM transactions t 
    JOIN items i ON t.item_id = i.id 
    WHERE t.student_number = '$student_id' AND t.return_date IS NOT NULL 
    ORDER BY t.return_date DESC LIMIT 10
");
while($row = $historyQ->fetch_assoc()) {
    $data["history"][] = $row;
}

$pendingQ = $conn->query("
    SELECT id, request_date, status 
    FROM borrow_requests 
    WHERE student_number = '$student_id' AND status = 'Pending' 
    ORDER BY request_date DESC
");

while($row = $pendingQ->fetch_assoc()) {
    $req_id = $row['id'];
    
    $itemsQ = $conn->query("
        SELECT i.name 
        FROM borrow_request_items bri 
        JOIN items i ON bri.item_id = i.id 
        WHERE bri.request_id = $req_id
    ");
    
    $item_names = [];
    while($iRow = $itemsQ->fetch_assoc()) {
        $item_names[] = $iRow['name'];
    }
    
    $row['items'] = $item_names; 
    $data["pending"][] = $row;
}

echo json_encode($data);
?>