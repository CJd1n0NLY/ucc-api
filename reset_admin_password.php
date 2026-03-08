<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['id']) && isset($input['new_password'])) {
    
    $id = intval($input['id']);
    $new_password = $input['new_password'];

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password reset successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to reset password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>