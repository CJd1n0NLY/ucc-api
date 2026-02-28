<?php
include 'db.php';

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if (empty($branch_id)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM departments WHERE is_archived = 0 AND branch_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>