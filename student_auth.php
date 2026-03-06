<?php
include 'db.php';
include 'mail_helper.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit();
}

$action = isset($data['action']) ? $data['action'] : 'login';
$email = $conn->real_escape_string($data['email']);
$password = $data['password'];

if ($action === 'register') {
    $full_name = $conn->real_escape_string($data['full_name']);
    $student_number = $conn->real_escape_string($data['student_number']);
    $course_section = strtoupper(trim($conn->real_escape_string($data['course_section'])));
    $branch_id = isset($data['branch_id']) ? intval($data['branch_id']) : 1;

    $force_section = isset($data['force_section']) ? $data['force_section'] : false;

    $check = $conn->query("SELECT * FROM students WHERE email = '$email' OR student_number = '$student_number'");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Error: Email or Student Number is already registered."]);
        exit();
    }

    if (!$force_section) {
        $checkExact = $conn->query("SELECT DISTINCT course_section FROM students WHERE course_section = '$course_section'");
        
        if ($checkExact->num_rows == 0) {
            $normalized_input = preg_replace('/[^A-Z0-9]/', '', $course_section);
            
            $allSections = $conn->query("SELECT DISTINCT course_section FROM students WHERE course_section != ''");
            $suggestion = "";

            while ($row = $allSections->fetch_assoc()) {
                $existing = $row['course_section'];
                $normalized_existing = preg_replace('/[^A-Z0-9]/', '', $existing);
                
                if ($normalized_input === $normalized_existing) {
                    $suggestion = $existing;
                    break; 
                }
            }

            if ($suggestion !== "") {
                echo json_encode(["status" => "suggest", "suggestion" => $suggestion]);
                exit();
            }
        }
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $token = bin2hex(random_bytes(16));

    $sql = "INSERT INTO students (student_number, full_name, course_section, email, password, branch_id, is_verified, verification_token) 
            VALUES ('$student_number', '$full_name', '$course_section', '$email', '$hashed_password', $branch_id, 0, '$token')";

    if ($conn->query($sql)) {
        $frontend_url = isset($data['frontend_url']) ? rtrim($data['frontend_url'], '/') : 'http://localhost:5173';
        $verify_link = $frontend_url . "/verify-email?token=" . $token;
        
        $mailSent = sendNotification($email, $full_name, 'VERIFY_EMAIL', [], $verify_link);
        
        if ($mailSent) {
            echo json_encode([
                "status" => "success", 
                "message" => "Registration successful! Please check your email to verify your account."
            ]);
        } else {
            $conn->query("DELETE FROM students WHERE student_number = '$student_number'");
            
            echo json_encode([
                "status" => "error", 
                "message" => "Could not send verification email. Please check your email address and try again."
            ]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }

} else if ($action === 'login') {
    $sql = "SELECT s.*, b.name as branch_name, b.slug as branch_slug 
            FROM students s 
            LEFT JOIN branches b ON s.branch_id = b.id 
            WHERE s.email = '$email'";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Check if verified BEFORE checking passwords
        if ($student['is_verified'] == 0) {
            echo json_encode(["status" => "error", "message" => "Please check your email and verify your account before logging in."]);
            exit();
        }

        $is_password_correct = false;
        
        if (password_verify($password, $student['password'])) {
            $is_password_correct = true;
        } 
        else if ($password === $student['password']) { 
            $is_password_correct = true;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE students SET password = '$hashed_password' WHERE id = {$student['id']}");
        }

        if ($is_password_correct) {
            unset($student['password']); 
            echo json_encode(["status" => "success", "data" => $student]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No account found with that email."]);
    }
}
?>