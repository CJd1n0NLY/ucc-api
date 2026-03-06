<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json"); 

if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    $sql = "SELECT student_number FROM students WHERE verification_token = '$token' AND is_verified = 0";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $updateQuery = "UPDATE students SET is_verified = 1, verification_token = NULL WHERE verification_token = '$token'";
        
        if ($conn->query($updateQuery)) {
            echo json_encode([
                "status" => "success", 
                "message" => "Your account has been successfully activated. You can now log in."
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "A database error occurred while verifying your account."
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "This verification link is invalid, expired, or your account is already verified."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "No token provided."
    ]);
}
?>