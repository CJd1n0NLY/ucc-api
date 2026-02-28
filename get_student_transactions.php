<?php
include 'db.php';

$student_number = $_GET['student_number'];

$sql = "SELECT t.id as transaction_id, t.borrow_date, t.room, t.teacher_name, i.name as item_name, i.id as item_id, d.name as dept_name 
        FROM transactions t
        JOIN items i ON t.item_id = i.id
        JOIN departments d ON i.department_id = d.id
        WHERE t.student_number = '$student_number' AND t.return_date IS NULL";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>