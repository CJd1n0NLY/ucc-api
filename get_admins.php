<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$query = "SELECT 
            a.id, a.staff_id, a.username, a.full_name, a.role, a.is_active, a.last_login, a.branch_id,
            b.name as branch_name, 
            COUNT(t.id) as transactions_count 
          FROM admins a 
          LEFT JOIN branches b ON a.branch_id = b.id 
          LEFT JOIN transactions t ON (a.id = t.applied_by OR a.id = t.received_by)
          WHERE a.role != 'super_admin'
          GROUP BY a.id 
          ORDER BY a.full_name ASC";
          
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