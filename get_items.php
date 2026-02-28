<?php
include 'db.php';
include 'auto_update_status.php'; 

$dept = isset($_GET['dept']) ? $_GET['dept'] : 'all';
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if (empty($branch_id)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT i.*, d.name as department_name, d.slug as department_slug,
        t.borrow_date, t.room, t.teacher_name,
        s.full_name as borrower_name, s.student_number,
        a1.full_name as applied_by_name,
        a2.full_name as issued_by_name
        FROM items i
        LEFT JOIN departments d ON i.department_id = d.id
        LEFT JOIN transactions t ON i.id = t.item_id AND t.return_date IS NULL
        LEFT JOIN students s ON t.student_number = s.student_number
        LEFT JOIN admins a1 ON t.applied_by = a1.id
        LEFT JOIN admins a2 ON t.issued_by = a2.id
        WHERE i.branch_id = ?";

if ($dept !== 'all') {
    $sql .= " AND i.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $branch_id, $dept); 
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
}

$stmt->execute();
$result = $stmt->get_result();

$items = array();
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>