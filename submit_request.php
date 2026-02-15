<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $input['student_number'];
    $item_ids = $input['item_ids'];
    $request_date = date('Y-m-d H:i:s');

    if (empty($student_number) || empty($item_ids)) {
        echo json_encode(["status" => "error", "message" => "Missing data."]);
        exit();
    }

    $overdueCheck = $conn->query("
        SELECT id FROM transactions 
        WHERE student_number = '$student_number' 
        AND return_date IS NULL 
        AND DATE_ADD(borrow_date, INTERVAL 1 DAY) < NOW()
    ");

    if ($overdueCheck->num_rows > 0) {
         echo json_encode(["status" => "error", "message" => "You have overdue items. Please return them before requesting new ones."]);
         exit();
    }

    $id_list = implode(",", $item_ids);
    $availCheck = $conn->query("SELECT id FROM items WHERE id IN ($id_list) AND status != 'Available'");
    if ($availCheck->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "One or more items in your cart are no longer available."]);
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO borrow_requests (student_number, request_date, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("ss", $student_number, $request_date);
        $stmt->execute();
        $request_id = $conn->insert_id;

        $itemStmt = $conn->prepare("INSERT INTO borrow_request_items (request_id, item_id) VALUES (?, ?)");
        $updateItemStmt = $conn->prepare("UPDATE items SET status = 'Reserved' WHERE id = ?");

        foreach ($item_ids as $item_id) {
            $itemStmt->bind_param("ii", $request_id, $item_id);
            $itemStmt->execute();

            $updateItemStmt->bind_param("i", $item_id);
            $updateItemStmt->execute();
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Request submitted successfully! Waiting for Admin approval."]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Transaction failed: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
}
?>