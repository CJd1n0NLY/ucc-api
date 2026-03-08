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
    
    $room = $input['room'] ?? null;
    $teacher_name = $input['teacher_name'] ?? null;
    $admin_id = $input['admin_id'] ?? null;
    $branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;

    if (empty($branch_id)) {
        echo json_encode(["status" => "error", "message" => "Missing branch identity."]);
        exit;
    }

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
            $due_date = date('Y-m-d 20:00:00');
            
            // Insert the transaction and tag it to the branch
            $stmt = $conn->prepare("INSERT INTO transactions (student_number, item_id, borrow_date, due_date, status, room, teacher_name, applied_by, issued_by, branch_id) VALUES (?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?)");
            $updateStmt = $conn->prepare("UPDATE items SET status = 'Borrowed' WHERE id = ?");

            $borrowedItemsData = [];
            
            foreach ($approved_item_ids as $item_id) {
                // Bind sisssiiii parameters
                $stmt->bind_param("sissssiii", $student_number, $item_id, $borrow_date, $due_date, $room, $teacher_name, $admin_id, $admin_id, $branch_id);
                $stmt->execute();

                $updateStmt->bind_param("i", $item_id);
                $updateStmt->execute();

                $itemQ = $conn->query("SELECT name, asset_tag FROM items WHERE id = $item_id");
                if($row = $itemQ->fetch_assoc()) {
                    $borrowedItemsData[] = $row;
                }
            }

            $conn->query("UPDATE borrow_requests SET status = 'Approved' WHERE id = $request_id");

            $updateAvailableStmt = $conn->prepare("UPDATE items SET status = 'Available' WHERE id = ?");
            foreach ($all_requested_items as $item_id) {
                if (!in_array($item_id, $approved_item_ids)) {
                    $updateAvailableStmt->bind_param("i", $item_id);
                    $updateAvailableStmt->execute();
                }
            }

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
            $rejection_reason = $input['rejection_reason'] ?? null;
            $updateAvailableStmt = $conn->prepare("UPDATE items SET status = 'Available' WHERE id = ?");
            foreach ($all_requested_items as $item_id) {
                $updateAvailableStmt->bind_param("i", $item_id);
                $updateAvailableStmt->execute();
            }
            
            $declineStmt = $conn->prepare("UPDATE borrow_requests SET status = 'Declined', rejection_reason = ? WHERE id = ?");
            $declineStmt->bind_param("si", $rejection_reason, $request_id);
            $declineStmt->execute();

            $msg = "Request declined and items unreserved.";
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => $msg]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>