<?php
include 'db.php';

$student_number = isset($_GET['student_number']) ? $_GET['student_number'] : '';
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if (empty($student_number) || empty($branch_id)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT t.id as transaction_id, t.borrow_date, t.room, t.teacher_name, i.name as item_name, i.id as item_id, d.name as dept_name 
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    JOIN departments d ON i.department_id = d.id
    WHERE t.student_number = ? AND t.return_date IS NULL AND t.branch_id = ?
");

$stmt->bind_param("si", $student_number, $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>