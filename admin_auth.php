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
        
        if (isset($admin['is_active']) && $admin['is_active'] == 0) {
            echo json_encode(["status" => "error", "message" => "This account has been deactivated. Please contact the Super Admin."]);
            exit();
        }

        $is_password_correct = false;
        
        if (password_verify($password, $admin['password'])) {
            $is_password_correct = true;
        } 
        else if ($password === $admin['password']) { 
            $is_password_correct = true;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_pw_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_pw_stmt->bind_param("si", $hashed_password, $admin['id']);
            $update_pw_stmt->execute();
        }

        if ($is_password_correct) {
            $update_login_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $update_login_stmt->bind_param("i", $admin['id']);
            $update_login_stmt->execute();

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