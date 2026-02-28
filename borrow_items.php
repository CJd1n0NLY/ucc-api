<?php
include 'db.php';
include 'mail_helper.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $input['student_number'];
    $item_ids = $input['item_ids'];
    
    $room = $input['room'] ?? null;
    $teacher_name = $input['teacher_name'] ?? null;
    $admin_id = $input['admin_id'] ?? null;
    
    $borrow_date = date('Y-m-d H:i:s');

    if (empty($student_number) || empty($item_ids)) {
        echo json_encode(["status" => "error", "message" => "Missing data."]);
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO transactions (student_number, item_id, borrow_date, status, room, teacher_name, applied_by, issued_by) VALUES (?, ?, ?, 'Active', ?, ?, ?, ?)");
        $updateStmt = $conn->prepare("UPDATE items SET status = 'Borrowed' WHERE id = ?");

        $borrowedItemsData = []; 

        foreach ($item_ids as $item_id) {
            $stmt->bind_param("sisssii", $student_number, $item_id, $borrow_date, $room, $teacher_name, $admin_id, $admin_id);
            $stmt->execute();

            $updateStmt->bind_param("i", $item_id);
            $updateStmt->execute();

            $itemQ = $conn->query("SELECT name, asset_tag FROM items WHERE id = $item_id");
            if($row = $itemQ->fetch_assoc()) {
                $borrowedItemsData[] = $row;
            }
        }

        $conn->commit();

        $sQuery = $conn->query("SELECT full_name, email FROM students WHERE student_number = '$student_number'");
        $student = $sQuery->fetch_assoc();

        if ($student && !empty($student['email'])) {
            sendNotification($student['email'], $student['full_name'], 'BORROW', $borrowedItemsData);
        }

        echo json_encode(["status" => "success", "message" => "Items borrowed successfully."]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request."]);
}
?>