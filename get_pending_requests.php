<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$requests = [];

$reqQuery = $conn->query("
    SELECT r.id as request_id, r.student_number, r.request_date, r.room, r.teacher_name, s.full_name, s.course_section, s.email 
    FROM borrow_requests r 
    JOIN students s ON r.student_number = s.student_number 
    WHERE r.status = 'Pending' 
    ORDER BY r.request_date ASC
");

if (!$reqQuery) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

while ($row = $reqQuery->fetch_assoc()) {
    $req_id = $row['request_id'];
    
    $itemQuery = $conn->query("
        SELECT i.id, i.name, i.asset_tag, i.department_id, d.name as dept_name, i.image
        FROM borrow_request_items bri
        JOIN items i ON bri.item_id = i.id
        LEFT JOIN departments d ON i.department_id = d.id
        WHERE bri.request_id = $req_id
    ");
    
    $items = [];
    while ($itemRow = $itemQuery->fetch_assoc()) {
        $items[] = $itemRow;
    }
    $row['items'] = $items;
    $requests[] = $row;
}

echo json_encode(["status" => "success", "data" => $requests]);
?>