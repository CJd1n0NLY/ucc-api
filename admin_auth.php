<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['action']) && $input['action'] === 'login') {
    $username = $input['username'];
    $password = $input['password'];

    $stmt = $conn->prepare("
        SELECT a.*, b.name as branch_name, b.slug as branch_slug 
        FROM admins a 
        LEFT JOIN branches b ON a.branch_id = b.id 
        WHERE a.username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if ($password === $admin['password']) { 
            unset($admin['password']); 
            echo json_encode(["status" => "success", "data" => $admin]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Admin user not found."]);
    }
}
?>