<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$query = "SELECT 
            s.student_number, s.full_name, s.course_section, s.email, s.branch_id, s.account_created_at,
            b.name as branch_name 
          FROM students s 
          LEFT JOIN branches b ON s.branch_id = b.id 
          ORDER BY s.account_created_at DESC, s.full_name ASC";
          
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