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
        t.id, t.borrow_date, t.return_date, t.status, t.room, t.teacher_name,
        s.full_name as student_name, s.student_number, s.course_section,
        i.name as item_name, i.asset_tag,
        a1.full_name as applied_by_name,
        a2.full_name as issued_by_name,
        a3.full_name as received_by_name
    FROM transactions t
    JOIN students s ON t.student_number = s.student_number
    JOIN items i ON t.item_id = i.id
    LEFT JOIN admins a1 ON t.applied_by = a1.id
    LEFT JOIN admins a2 ON t.issued_by = a2.id
    LEFT JOIN admins a3 ON t.received_by = a3.id
    WHERE DATE(t.borrow_date) BETWEEN ? AND ? AND t.branch_id = ?
    ORDER BY t.borrow_date DESC
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