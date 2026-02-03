<?php
include 'db.php';
$result = $conn->query("SELECT * FROM departments WHERE is_archived = 0 ORDER BY name ASC");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>