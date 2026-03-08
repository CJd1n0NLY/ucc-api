<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['id']) && isset($input['staff_id']) && isset($input['username']) && isset($input['full_name']) && isset($input['role'])) {
    
    $id = intval($input['id']);
    $staff_id = $input['staff_id'];
    $username = $input['username'];
    $full_name = $input['full_name'];
    $role = $input['role'];
    $branch_id = ($role === 'super_admin' || empty($input['branch_id'])) ? null : intval($input['branch_id']);

    $check = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR staff_id = ?) AND id != ?");
    $check->bind_param("ssi", $username, $staff_id, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username or Staff ID is already taken by another user."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE admins SET staff_id = ?, username = ?, full_name = ?, role = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $staff_id, $username, $full_name, $role, $branch_id, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff details updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update staff details."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>