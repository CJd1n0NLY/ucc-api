<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$student_number = $data['student_number'];
$item_ids = $data['item_ids'];

if (empty($student_number) || empty($item_ids)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit();
}

$conn->begin_transaction();

try {
    foreach ($item_ids as $id) {
        $stmt1 = $conn->prepare("INSERT INTO transactions (student_number, item_id, status) VALUES (?, ?, 'Active')");
        $stmt1->bind_param("si", $student_number, $id);
        $stmt1->execute();

        $stmt2 = $conn->prepare("UPDATE items SET status = 'Borrowed' WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Items successfully borrowed!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Transaction failed: " . $e->getMessage()]);
}
?>