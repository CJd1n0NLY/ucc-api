<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['id']) && isset($input['is_active'])) {
    
    $id = intval($input['id']);
    $is_active = intval($input['is_active']);

    $stmt = $conn->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_active, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff status updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update status."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}
?>