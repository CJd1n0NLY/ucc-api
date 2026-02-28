<?php
include 'db.php';
include 'mail_helper.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $input['student_number'];
    $returns = $input['returns'];
    
    $admin_id = $input['admin_id'] ?? null;

    $conn->begin_transaction();

    try {
        $returnedItemsData = [];
        
        $stmt1 = $conn->prepare("UPDATE transactions SET return_date = NOW(), status = ?, received_by = ? WHERE id = ?");
        $stmt2 = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");

        foreach ($returns as $ret) {
            $transaction_id = $ret['transaction_id'];
            $item_id = $ret['item_id'];
            $condition = $ret['condition'];

            $final_status = 'Returned'; 
            if ($condition == 'Damaged') $final_status = 'Damaged';
            if ($condition == 'Lost') $final_status = 'Lost';

            $stmt1->bind_param("sii", $final_status, $admin_id, $transaction_id);
            $stmt1->execute();

            $item_status = ($condition == 'Good') ? 'Available' : (($condition == 'Lost') ? 'Lost' : 'Maintenance');
            $stmt2->bind_param("si", $item_status, $item_id);
            $stmt2->execute();

            $iQ = $conn->query("SELECT name, asset_tag FROM items WHERE id = $item_id");
            if ($row = $iQ->fetch_assoc()) {
                $returnedItemsData[] = $row;
            }
        }

        $conn->commit();

        $sQuery = $conn->query("SELECT full_name, email FROM students WHERE student_number = '$student_number'");
        $student = $sQuery->fetch_assoc();

        if ($student && !empty($student['email']) && count($returnedItemsData) > 0) {
            sendNotification($student['email'], $student['full_name'], 'RETURN', $returnedItemsData);
        }

        echo json_encode(["status" => "success", "message" => count($returns) . " items successfully returned."]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>