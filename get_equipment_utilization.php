<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

require 'db.php';

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end = $_GET['end'] ?? date('Y-m-d');
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if (empty($branch_id)) { echo json_encode(["status" => "error", "message" => "Missing branch_id"]); exit; }

$query = "
    SELECT 
        i.name as item_name,
        i.asset_tag,
        d.name as department_name,
        COUNT(t.id) as usage_count
    FROM items i
    LEFT JOIN departments d ON i.department_id = d.id
    LEFT JOIN transactions t ON i.id = t.item_id 
        AND DATE(t.borrow_date) BETWEEN ? AND ?
    WHERE i.branch_id = ?
    GROUP BY i.id
    ORDER BY usage_count DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("ssi", $start, $end, $branch_id);
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