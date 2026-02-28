<?php
require 'db.php';
header('Content-Type: application/json');

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end = $_GET['end'] ?? date('Y-m-d');

$query = "
    SELECT 
        s.full_name as student_name,
        s.student_number,
        s.course_section,
        i.name as item_name,
        i.asset_tag,
        t.borrow_date,
        t.status as transaction_status,
        DATEDIFF(NOW(), t.return_date) as days_overdue
    FROM transactions t
    JOIN students s ON t.student_number = s.student_number
    JOIN items i ON t.item_id = i.id
    WHERE 
        t.status = 'Overdue'
        OR t.status = 'Lost' 
        OR t.status = 'Damaged'
    AND DATE(t.borrow_date) BETWEEN ? AND ?
    ORDER BY t.status DESC, days_overdue DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        
        if ($row['transaction_status'] === 'Lost' || $row['transaction_status'] === 'Damaged') {
            $row['days_overdue'] = 'N/A'; 
        }
        
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data], JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>