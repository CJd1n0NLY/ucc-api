<?php
include 'db.php';
include 'mail_helper.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $request_id = $input['request_id'];
    $action = $input['action']; 
    $approved_item_ids = isset($input['approved_item_ids']) ? $input['approved_item_ids'] : [];

    $conn->begin_transaction();

    try {
        $reqQ = $conn->query("SELECT student_number FROM borrow_requests WHERE id = $request_id");
        $student_number = $reqQ->fetch_assoc()['student_number'];

        $itemsQ = $conn->query("SELECT item_id FROM borrow_request_items WHERE request_id = $request_id");
        $all_requested_items = [];
        while ($row = $itemsQ->fetch_assoc()) {
            $all_requested_items[] = $row['item_id'];
        }

        if ($action === 'approve') {
            $borrow_date = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO transactions (student_number, item_id, borrow_date, status) VALUES (?, ?, ?, 'Active')");
            $updateBorrowedStmt = $conn->prepare("UPDATE items SET status = 'Borrowed' WHERE id = ?");
            $updateAvailableStmt = $conn->prepare("UPDATE items SET status = 'Available' WHERE id = ?");

            $borrowedItemsData = [];

            foreach ($all_requested_items as $item_id) {
                if (in_array($item_id, $approved_item_ids)) {
                    $stmt->bind_param("sis", $student_number, $item_id, $borrow_date);
                    $stmt->execute();
                    
                    $updateBorrowedStmt->bind_param("i", $item_id);
                    $updateBorrowedStmt->execute();

                    $itemDataQ = $conn->query("SELECT name, asset_tag FROM items WHERE id = $item_id");
                    $borrowedItemsData[] = $itemDataQ->fetch_assoc();
                } else {
                    $updateAvailableStmt->bind_param("i", $item_id);
                    $updateAvailableStmt->execute();
                }
            }

            $conn->query("UPDATE borrow_requests SET status = 'Approved' WHERE id = $request_id");

            $sQuery = $conn->query("SELECT full_name, email FROM students WHERE student_number = '$student_number'");
            $student = $sQuery->fetch_assoc();
            if ($student && !empty($student['email']) && count($borrowedItemsData) > 0) {
                if (count($approved_item_ids) < count($all_requested_items)) {
                    sendNotification($student['email'], $student['full_name'], 'PARTIAL_BORROW', $borrowedItemsData);
                } else {
                    sendNotification($student['email'], $student['full_name'], 'BORROW', $borrowedItemsData);
                }
            }

            $msg = count($approved_item_ids) < count($all_requested_items) 
                ? "Partial request approved. Unchecked items were returned to inventory." 
                : "All items officially released to student.";

        } else if ($action === 'decline') {
            $updateAvailableStmt = $conn->prepare("UPDATE items SET status = 'Available' WHERE id = ?");
            foreach ($all_requested_items as $item_id) {
                $updateAvailableStmt->bind_param("i", $item_id);
                $updateAvailableStmt->execute();
            }
            
            $conn->query("UPDATE borrow_requests SET status = 'Declined' WHERE id = $request_id");
            $msg = "Request declined and items unreserved.";
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => $msg]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
}
?>