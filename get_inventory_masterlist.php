<?php
require 'db.php';
header('Content-Type: application/json');

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if (empty($branch_id)) { echo json_encode(["status" => "error", "message" => "Missing branch_id"]); exit; }

$query = "
    SELECT 
        i.id, i.name, i.asset_tag, i.status, i.created_at,
        d.name as department_name,
        s.full_name as borrower_name
    FROM items i
    LEFT JOIN departments d ON i.department_id = d.id
    LEFT JOIN transactions t ON i.id = t.item_id AND t.status = 'Active'
    LEFT JOIN students s ON t.student_number = s.student_number
    WHERE i.branch_id = ?
    ORDER BY d.name ASC, i.name ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>