<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$admin_id = isset($_GET['admin_id']) ? $conn->real_escape_string($_GET['admin_id']) : '';
$role = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';

$sql = "
    SELECT 
        t.id, 
        t.student_number, 
        t.borrow_date, 
        t.return_date, 
        t.status, 
        t.room, 
        t.teacher_name,
        s.full_name AS student_name,
        i.name AS item_name,
        i.asset_tag,
        a1.full_name AS applied_by_name,
        a2.full_name AS issued_by_name,
        a3.full_name AS received_by_name
    FROM transactions t
    JOIN students s ON t.student_number = s.student_number
    JOIN items i ON t.item_id = i.id
    LEFT JOIN admins a1 ON t.applied_by = a1.id
    LEFT JOIN admins a2 ON t.issued_by = a2.id
    LEFT JOIN admins a3 ON t.received_by = a3.id
";

if ($role !== 'super_admin' && !empty($admin_id)) {
    $sql .= " WHERE t.applied_by = '$admin_id' OR t.issued_by = '$admin_id' OR t.received_by = '$admin_id'";
}

$sql .= " ORDER BY t.borrow_date DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode($transactions);
?>