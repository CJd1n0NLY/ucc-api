<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$student_number = $data['student_number'];
$full_name = $data['full_name'];
$course_section = $data['course_section'];

if (!$student_number || !$full_name) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO students (student_number, full_name, course_section) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $student_number, $full_name, $course_section);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>