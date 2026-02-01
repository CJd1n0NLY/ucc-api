<?php
include 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'];
    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error"]);
} 
elseif ($method == 'DELETE') {
    $id = $_GET['id'];
    $conn->query("DELETE FROM departments WHERE id=$id");
    echo json_encode(["status" => "deleted"]);
}
?>