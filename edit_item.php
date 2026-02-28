<?php
include 'db.php';

$imagePath = isset($_POST['existing_image']) ? $_POST['existing_image'] : "";

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $filename = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $imagePath = $target_file;
    }
}

$id = $_POST['id'];
$name = $_POST['name'];
$department_id = $_POST['department_id'];
$status = $_POST['status']; 
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
$batch_mode = isset($_POST['batch_mode']) && $_POST['batch_mode'] === 'true';
$original_base_name = isset($_POST['original_base_name']) ? $_POST['original_base_name'] : "";

if (empty($id) || empty($name) || empty($department_id) || empty($branch_id)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields or branch identity."]);
    exit();
}

$conn->begin_transaction();

try {
    if ($batch_mode && !empty($original_base_name)) {
        $stmt = $conn->prepare("UPDATE items SET department_id = ?, image = ? WHERE name LIKE CONCAT(?, '%') AND branch_id = ?");
        $stmt->bind_param("issi", $department_id, $imagePath, $original_base_name, $branch_id);
        $stmt->execute();
        
        $msg = "Batch update successful!";
    } else {
        
        $checkStmt = $conn->prepare("SELECT id FROM items WHERE name = ? AND id != ? AND branch_id = ?");
        $checkStmt->bind_param("sii", $name, $id, $branch_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Item name '$name' already exists in your branch!"]);
            exit();
        }

        $stmt = $conn->prepare("UPDATE items SET name = ?, department_id = ?, status = ?, image = ? WHERE id = ? AND branch_id = ?");
        $stmt->bind_param("sisssii", $name, $department_id, $status, $imagePath, $id, $branch_id);
        $stmt->execute();
        
        $msg = "Item updated successfully.";
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => $msg]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
?>