<?php
include 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents("php://input"), true);

if ($method == 'POST') {
    $name = $input['name'];
    if(empty($name)) { echo json_encode(["status" => "error", "message" => "Name is required"]); exit(); }

    $check = $conn->prepare("SELECT id FROM departments WHERE name = ? AND is_archived = 0");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();
    
    if($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Department already exists!"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $conn->error]);
} 

elseif ($method == 'PUT') {
    $id = $input['id'];
    $name = $input['name'];

    if(empty($id) || empty($name)) { echo json_encode(["status" => "error", "message" => "Invalid data"]); exit(); }

    $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $conn->error]);
}

elseif ($method == 'DELETE') {
    $id = $_GET['id'];
    
    if($conn->query("UPDATE departments SET is_archived = 1 WHERE id=$id")) {
        echo json_encode(["status" => "success", "message" => "Department archived successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>