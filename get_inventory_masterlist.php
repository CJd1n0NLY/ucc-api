<?php
require 'db.php';
header('Content-Type: application/json');

$query = "
    SELECT 
        i.id, i.name, i.asset_tag, i.status, i.created_at,
        d.name as department_name,
        s.full_name as borrower_name
    FROM items i
    LEFT JOIN departments d ON i.department_id = d.id
    LEFT JOIN transactions t ON i.id = t.item_id AND t.status = 'Active'
    LEFT JOIN students s ON t.student_number = s.student_number
    ORDER BY d.name ASC, i.name ASC
";

$result = $conn->query($query);

if ($result) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>