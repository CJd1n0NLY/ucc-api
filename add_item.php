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
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$status = 'Available';

if (empty($name) || empty($department_id) || empty($branch_id)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields, including campus assignment."]);
    exit();
}

$conn->begin_transaction();

try {
    $slugStmt = $conn->prepare("SELECT slug FROM departments WHERE id = ? AND branch_id = ?");
    $slugStmt->bind_param("ii", $department_id, $branch_id);
    $slugStmt->execute();
    $slugRes = $slugStmt->get_result();
    $slugRow = $slugRes->fetch_assoc();
    
    $dept_slug = !empty($slugRow['slug']) ? strtoupper($slugRow['slug']) : 'DEPT'; 

    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM items WHERE name LIKE CONCAT(?, '%') AND branch_id = ?");
    $countStmt->bind_param("si", $name, $branch_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $row = $countResult->fetch_assoc();
    
    $start_number = $row['total'] + 1;

    for ($i = 0; $i < $quantity; $i++) {
        $random_hex = strtoupper(bin2hex(random_bytes(3)));
        $asset_tag = "UCC-" . $dept_slug . "-" . $random_hex; 

        $current_num = $start_number + $i;
        $finalName = "$name #$current_num";

        $stmt = $conn->prepare("INSERT INTO items (name, department_id, status, image, asset_tag, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssi", $finalName, $department_id, $status, $imagePath, $asset_tag, $branch_id);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "$quantity Item(s) added successfully!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
?>