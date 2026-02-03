<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);
$action = isset($input['action']) ? $input['action'] : '';

if ($action === 'login') {
    $email = $input['email']; 
    $password = $input['password'];

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        if ($password === $student['password']) {
            unset($student['password']); 
            
            echo json_encode([
                "status" => "success", 
                "data" => $student
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}
?>