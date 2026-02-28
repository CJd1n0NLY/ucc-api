<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $result = $conn->query("SELECT * FROM branches ORDER BY id ASC");
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if ($method == 'POST') {
    $name = $input['name'];
    $slug = strtoupper($input['slug']);

    if(empty($name) || empty($slug)) { 
        echo json_encode(["status" => "error", "message" => "Name and Slug code are required."]); 
        exit(); 
    }

    $check = $conn->prepare("SELECT id FROM branches WHERE name = ? OR slug = ?");
    $check->bind_param("ss", $name, $slug);
    $check->execute();
    if($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "A branch with that Name or Slug already exists."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO branches (name, slug) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $slug);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} 

elseif ($method == 'PUT') {
    $id = $input['id'];
    $name = $input['name'];
    $slug = strtoupper($input['slug']);
    $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

    if(empty($id) || empty($name) || empty($slug)) { 
        echo json_encode(["status" => "error", "message" => "Invalid data provided."]); 
        exit(); 
    }

    $stmt = $conn->prepare("UPDATE branches SET name = ?, slug = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssii", $name, $slug, $is_active, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>