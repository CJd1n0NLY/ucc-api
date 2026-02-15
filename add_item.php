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

$name = $_POST['name'];
$department_id = $_POST['department_id'];
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$status = 'Available';

if (empty($name) || empty($department_id)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
    exit();
}

$conn->begin_transaction();

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM items WHERE name LIKE CONCAT(?, '%')");
    $countStmt->bind_param("s", $name);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $row = $countResult->fetch_assoc();
    
    $start_number = $row['total'] + 1;

    for ($i = 0; $i < $quantity; $i++) {
        $asset_tag = "UCC-" . strtoupper(bin2hex(random_bytes(3))); 

        $current_num = $start_number + $i;
        $finalName = "$name #$current_num";

        $stmt = $conn->prepare("INSERT INTO items (name, department_id, status, image, asset_tag) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $finalName, $department_id, $status, $imagePath, $asset_tag);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "$quantity Item(s) added successfully!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
?>