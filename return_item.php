<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);

$transaction_id = $data['transaction_id'];
$item_id = $data['item_id'];
$condition = $data['condition'];

$conn->begin_transaction();

try {
    $final_status = 'Returned'; 
    if ($condition == 'Damaged') $final_status = 'Damaged';
    if ($condition == 'Lost') $final_status = 'Lost';

    $stmt1 = $conn->prepare("UPDATE transactions SET return_date = NOW(), status = ? WHERE id = ?");
    $stmt1->bind_param("si", $final_status, $transaction_id);
    $stmt1->execute();

    $item_status = ($condition == 'Good') ? 'Available' : (($condition == 'Lost') ? 'Lost' : 'Maintenance');
    $stmt2 = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
    $stmt2->bind_param("si", $item_status, $item_id);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>