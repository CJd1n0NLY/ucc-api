<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $student_number = $input['student_number'];
    $full_name = $input['full_name'];
    $course_section = $input['course_section'];
    $email = isset($input['email']) ? $input['email'] : null;
    $password = isset($input['password']) ? $input['password'] : $student_number; 

    if (empty($student_number) || empty($full_name) || empty($course_section)) {
        echo json_encode(["status" => "error", "message" => "Name and Course are required."]);
        exit();
    }

    $check = $conn->query("SELECT student_number FROM students WHERE student_number = '$student_number'");
    
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Student ID already exists!"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO students (student_number, full_name, course_section, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $student_number, $full_name, $course_section, $email, $password);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Student registered successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data."]);
}
?>