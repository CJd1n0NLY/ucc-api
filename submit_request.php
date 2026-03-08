<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $input['student_number'] ?? null;
    $item_ids = $input['item_ids'] ?? [];
    $room = $input['room'] ?? null;     
    $teacher_name = $input['teacher_name'] ?? null;
    $request_date = date('Y-m-d H:i:s');
    $reason = $input['reason'] ?? null;

    if (empty($student_number) || empty($item_ids) || !is_array($item_ids) || empty($reason) || empty($room) || empty($teacher_name)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields (Room, Teacher, or Reason)."]);
        exit();
    }

    $overdueStmt = $conn->prepare("
        SELECT id FROM transactions 
        WHERE student_number = ? 
        AND return_date IS NULL 
        AND DATE_ADD(borrow_date, INTERVAL 1 DAY) < NOW()
    ");
    $overdueStmt->bind_param("s", $student_number);
    $overdueStmt->execute();
    $overdueCheck = $overdueStmt->get_result();

    if ($overdueCheck->num_rows > 0) {
         echo json_encode(["status" => "error", "message" => "You have overdue items. Please return them before requesting new ones."]);
         exit();
    }

    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    
    $types = str_repeat('i', count($item_ids)); 
    
    $availStmt = $conn->prepare("SELECT id FROM items WHERE id IN ($placeholders) AND status != 'Available'");
    $availStmt->bind_param($types, ...$item_ids); 
    $availStmt->execute();
    $availCheck = $availStmt->get_result();

    if ($availCheck->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "One or more items in your request are no longer available. Please refresh the catalog."]);
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO borrow_requests (student_number, request_date, status, room, teacher_name, request_reason) VALUES (?, ?, 'Pending', ?, ?, ?)");
        $stmt->bind_param("sssss", $student_number, $request_date, $room, $teacher_name, $reason);
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