<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);
$qr_code = $data['qr_code'];

$stmt = $conn->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->bind_param("s", $qr_code);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($student) {
    
    $overdue_sql = "SELECT COUNT(*) as count FROM transactions 
                    WHERE student_number = '$qr_code' 
                    AND status = 'Active' 
                    AND borrow_date < NOW() - INTERVAL 1 DAY";
    $overdue_count = $conn->query($overdue_sql)->fetch_assoc()['count'];

    $history_sql = "SELECT COUNT(*) as count FROM transactions 
                    WHERE student_number = '$qr_code' 
                    AND status IN ('Lost', 'Damaged')";
    $history_count = $conn->query($history_sql)->fetch_assoc()['count'];

    echo json_encode([
        "status" => "success", 
        "data" => $student,
        "flags" => [
            "overdue" => $overdue_count,
            "bad_history" => $history_count
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Student not found"]);
}
?>