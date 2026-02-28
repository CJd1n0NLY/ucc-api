<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $qr_code = $input['qr_code'];
    $branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;

    if (empty($branch_id)) {
        echo json_encode(["status" => "error", "message" => "Branch not specified."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM students WHERE student_number = ? AND branch_id = ?");
    $stmt->bind_param("si", $qr_code, $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        unset($student['password']); 

        $overdueQuery = "
            SELECT COUNT(*) as count 
            FROM transactions 
            WHERE student_number = ? 
            AND return_date IS NULL 
            AND (
                status = 'Overdue' 
                OR 
                borrow_date < (NOW() - INTERVAL 1 DAY)
            )
        ";
        $stmtOverdue = $conn->prepare($overdueQuery);
        $stmtOverdue->bind_param("s", $qr_code);
        $stmtOverdue->execute();
        $overdueCount = $stmtOverdue->get_result()->fetch_assoc()['count'];

        $historyQuery = "
            SELECT COUNT(*) as count 
            FROM transactions 
            WHERE student_number = ? 
            AND status IN ('Lost', 'Damaged')
        ";
        $stmtHistory = $conn->prepare($historyQuery);
        $stmtHistory->bind_param("s", $qr_code);
        $stmtHistory->execute();
        $historyCount = $stmtHistory->get_result()->fetch_assoc()['count'];

        echo json_encode([
            "status" => "success",
            "data" => $student,
            "flags" => [
                "overdue" => $overdueCount,
                "bad_history" => $historyCount
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Student not found"]);
    }
}
?>