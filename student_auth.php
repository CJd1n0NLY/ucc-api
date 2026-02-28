<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit();
}

$action = isset($data['action']) ? $data['action'] : 'login';
$email = $conn->real_escape_string($data['email']);
$password = $data['password'];

if ($action === 'register') {
    $full_name = $conn->real_escape_string($data['full_name']);
    $student_number = $conn->real_escape_string($data['student_number']);
    $course_section = $conn->real_escape_string($data['course_section']);
    
    $branch_id = isset($data['branch_id']) ? intval($data['branch_id']) : 1;

    $check = $conn->query("SELECT * FROM students WHERE email = '$email' OR student_number = '$student_number'");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Error: Email or Student Number is already registered."]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO students (student_number, full_name, course_section, email, password, branch_id) 
            VALUES ('$student_number', '$full_name', '$course_section', '$email', '$hashed_password', $branch_id)";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Registration successful!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }

} else if ($action === 'login') {
    $sql = "SELECT s.*, b.name as branch_name, b.slug as branch_slug 
            FROM students s 
            LEFT JOIN branches b ON s.branch_id = b.id 
            WHERE s.email = '$email'";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        $is_password_correct = false;
        
        if (password_verify($password, $student['password'])) {
            $is_password_correct = true;
        } 
        else if ($password === $student['password']) { 
            $is_password_correct = true;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE students SET password = '$hashed_password' WHERE id = {$student['id']}");
        }

        if ($is_password_correct) {
            unset($student['password']); 
            echo json_encode(["status" => "success", "data" => $student]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No account found with that email."]);
    }
}
?>