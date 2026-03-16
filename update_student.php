<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    if (!isset($input['original_student_number'])) {
        echo json_encode(["status" => "error", "message" => "Original Student Number is missing."]);
        exit();
    }

    $original_student_number = trim($conn->real_escape_string($input['original_student_number']));
    $student_number = trim($conn->real_escape_string($input['student_number']));
    $full_name = trim($conn->real_escape_string($input['full_name']));
    $course_section = strtoupper(trim($conn->real_escape_string($input['course_section'])));
    $branch_id = $conn->real_escape_string($input['branch_id']);

    // Validations
    if (!preg_match('/^\d{8}-[a-zA-Z]$/', $student_number)) {
        echo json_encode(["status" => "error", "message" => "Invalid Student Number. Format must be 8 digits, a dash, and 1 letter (e.g., 20221234-S)."]);
        exit();
    }

    if (!preg_match('/^[a-zA-Z\s\-\.]+$/', $full_name)) {
        echo json_encode(["status" => "error", "message" => "Invalid Full Name. Only letters, spaces, hyphens, and periods are allowed."]);
        exit();
    }

    if (empty($student_number) || empty($full_name) || empty($course_section) || empty($branch_id)) {
        echo json_encode(["status" => "error", "message" => "All fields including Branch are required."]);
        exit();
    }

    // Branch-Specific Duplicate Check
    $check_stmt = $conn->prepare("SELECT student_number FROM students WHERE student_number = ? AND branch_id = ? AND student_number != ?");
    $check_stmt->bind_param("sis", $student_number, $branch_id, $original_student_number);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "This Student Number already exists in the selected branch."]);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    $conn->begin_transaction();

    try {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $stmt = $conn->prepare("UPDATE students SET student_number = ?, full_name = ?, course_section = ?, branch_id = ? WHERE student_number = ?");
        $stmt->bind_param("sssss", $student_number, $full_name, $course_section, $branch_id, $original_student_number);
        $stmt->execute();
        $stmt->close();

        $stmt_tx = $conn->prepare("UPDATE transactions SET student_number = ? WHERE student_number = ?");
        $stmt_tx->bind_param("ss", $student_number, $original_student_number);
        $stmt_tx->execute();
        $stmt_tx->close();

        $stmt_br = $conn->prepare("UPDATE borrow_requests SET student_number = ? WHERE student_number = ?");
        $stmt_br->bind_param("ss", $student_number, $original_student_number);
        $stmt_br->execute();
        $stmt_br->close();
        
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        $conn->commit();

        echo json_encode(["status" => "success", "message" => "Student details and related records updated successfully."]);

    } catch (Exception $e) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Invalid data received."]);
}
?>