<?php
include 'db.php';
$result = $conn->query("SELECT * FROM departments");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>