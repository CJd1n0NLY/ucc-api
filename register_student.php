<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $conn->real_escape_string($input['student_number']);
    $full_name = $conn->real_escape_string($input['full_name']);
    $course_section = strtoupper(trim($conn->real_escape_string($input['course_section'])));
    $email = isset($input['email']) ? $conn->real_escape_string($input['email']) : null;
    $password = isset($input['password']) ? $input['password'] : $student_number; 
    $branch_id = isset($input['branch_id']) ? $conn->real_escape_string($input['branch_id']) : null;

    $force_section = isset($input['force_section']) ? $input['force_section'] : false;

    // Validations
    $student_number = trim($student_number);
    if (!preg_match('/^\d{8}-[a-zA-Z]$/', $student_number)) {
        echo json_encode(["status" => "error", "message" => "Invalid Student Number. Format must be 8 digits, a dash, and 1 letter (e.g., 20221234-S)."]);
        exit();
    }

    $full_name = trim($full_name);
    if (!preg_match('/^[a-zA-Z\s\-\.]+$/', $full_name)) {
        echo json_encode(["status" => "error", "message" => "Invalid Full Name. Only letters, spaces, hyphens, and periods are allowed."]);
        exit();
    }

    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long."]);
        exit();
    }

    if (empty($student_number) || empty($full_name) || empty($course_section) || empty($branch_id)) {
        echo json_encode(["status" => "error", "message" => "Name, Course, and Branch are required."]);
        exit();
    }

    // Check for duplicate student number specifically within the chosen branch
    $check = $conn->query("SELECT student_number FROM students WHERE student_number = '$student_number' AND branch_id = '$branch_id'");
    
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Student ID already exists in this branch!"]);
        exit();
    }

    // Section Suggestion Logic
    if (!$force_section) {
        $checkExact = $conn->query("SELECT DISTINCT course_section FROM students WHERE course_section = '$course_section'");
        
        if ($checkExact->num_rows == 0) {
            $normalized_input = preg_replace('/[^A-Z0-9]/', '', $course_section);
            
            $allSections = $conn->query("SELECT DISTINCT course_section FROM students WHERE course_section != ''");
            $suggestion = "";

            while ($row = $allSections->fetch_assoc()) {
                $existing = $row['course_section'];
                $normalized_existing = preg_replace('/[^A-Z0-9]/', '', $existing);
                
                if ($normalized_input === $normalized_existing) {
                    $suggestion = $existing;
                    break; 
                }
            }

            if ($suggestion !== "") {
                echo json_encode(["status" => "suggest", "suggestion" => $suggestion]);
                exit();
            }
        }
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (student_number, full_name, course_section, email, password, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $student_number, $full_name, $course_section, $email, $hashed_password, $branch_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Student registered successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data."]);
}
?>