<?php
include 'db.php';

$sql = "SELECT t.id, t.borrow_date, t.status, t.return_date,
        s.full_name as student_name, 
        i.name as item_name
        FROM transactions t
        JOIN students s ON t.student_number = s.student_number
        JOIN items i ON t.item_id = i.id
        ORDER BY t.borrow_date DESC 
        LIMIT 5";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $time_ago = time() - strtotime($row['borrow_date']);
    if ($time_ago < 60) {
        $row['time_display'] = "Just now";
    } elseif ($time_ago < 3600) {
        $row['time_display'] = floor($time_ago / 60) . " mins ago";
    } elseif ($time_ago < 86400) {
        $row['time_display'] = floor($time_ago / 3600) . " hours ago";
    } else {
        $row['time_display'] = date("M d, Y", strtotime($row['borrow_date']));
    }
    
    $data[] = $row;
}

echo json_encode($data);
?>