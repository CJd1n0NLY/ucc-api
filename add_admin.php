<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['username']) && isset($input['password']) && isset($input['full_name']) && isset($input['role'])) {
    
    $check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $check->bind_param("s", $input['username']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username already exists."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $input['username'], $input['password'], $input['full_name'], $input['role']);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff member added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add staff."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>