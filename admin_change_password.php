<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['admin_id']) && isset($input['current_password']) && isset($input['new_password'])) {
    
    $admin_id = intval($input['admin_id']);
    $current_password = $input['current_password'];
    $new_password = $input['new_password'];

    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if (password_verify($current_password, $admin['password'])) {
            
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_new_password, $admin_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database error: Could not update password."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect current password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>