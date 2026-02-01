<?php
include 'db.php';
include 'auto_update_status.php'; 

$dept = isset($_GET['dept']) ? $_GET['dept'] : 'all';

$sql = "SELECT i.*, d.name as department_name, 
        t.borrow_date, s.full_name as borrower_name, s.student_number
        FROM items i
        LEFT JOIN departments d ON i.department_id = d.id
        LEFT JOIN transactions t ON i.id = t.item_id AND t.return_date IS NULL
        LEFT JOIN students s ON t.student_number = s.student_number
        WHERE 1=1";

if($dept != 'all') {
    $sql .= " AND i.department_id = '$dept'";
}

$result = $conn->query($sql);

$items = array();
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>