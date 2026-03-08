<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['staff_id']) && isset($input['username']) && isset($input['password']) && isset($input['full_name']) && isset($input['role'])) {
    
    $staff_id = $input['staff_id'];
    $role = $input['role'];
    $branch_id = ($role === 'super_admin' || empty($input['branch_id'])) ? null : $input['branch_id'];

    $check = $conn->prepare("SELECT id FROM admins WHERE username = ? OR staff_id = ?");
    $check->bind_param("ss", $input['username'], $staff_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username or Staff ID already exists."]);
        exit;
    }

    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (staff_id, username, password, full_name, role, branch_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssi", $staff_id, $input['username'], $hashed_password, $input['full_name'], $role, $branch_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff member added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add staff."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>