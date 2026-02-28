<?php
include 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents("php://input"), true);

if ($method == 'POST') {
    $name = $input['name'];
    $slug = isset($input['slug']) ? $input['slug'] : '';
    $branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;

    if(empty($name) || empty($slug) || empty($branch_id)) { 
        echo json_encode(["status" => "error", "message" => "Name, Slug, and Branch are required"]); 
        exit(); 
    }

    $check = $conn->prepare("SELECT id FROM departments WHERE name = ? AND is_archived = 0");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();
    
    if($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Department already exists!"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO departments (name, slug, branch_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $slug, $branch_id);
    
    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $conn->error]);
} 

elseif ($method == 'PUT') {
    $id = $input['id'];
    $name = $input['name'];
    $slug = isset($input['slug']) ? $input['slug'] : '';

    if(empty($id) || empty($name) || empty($slug)) { 
        echo json_encode(["status" => "error", "message" => "Invalid data. Name and Slug required."]); 
        exit(); 
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE departments SET name = ?, slug = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $slug, $id);
        $stmt->execute();
        
        $updateItemsStmt = $conn->prepare("
            UPDATE items 
            SET asset_tag = CONCAT('UCC-', ?, '-', SUBSTRING_INDEX(asset_tag, '-', -1)) 
            WHERE department_id = ? AND asset_tag IS NOT NULL
        ");
        $updateItemsStmt->bind_param("si", $slug, $id);
        $updateItemsStmt->execute();

        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
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