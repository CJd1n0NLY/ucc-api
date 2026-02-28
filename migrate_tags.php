<?php
include 'db.php';

$sql = "SELECT i.id, d.slug 
        FROM items i 
        JOIN departments d ON i.department_id = d.id";
$result = $conn->query($sql);

$updatedCount = 0;

while ($row = $result->fetch_assoc()) {
    $item_id = $row['id'];
    $slug = !empty($row['slug']) ? strtoupper($row['slug']) : 'DEPT';
    
    $random_hex = strtoupper(bin2hex(random_bytes(3)));
    $new_tag = "UCC-" . $slug . "-" . $random_hex;

    $updateStmt = $conn->prepare("UPDATE items SET asset_tag = ? WHERE id = ?");
    $updateStmt->bind_param("si", $new_tag, $item_id);
    
    if ($updateStmt->execute()) {
        $updatedCount++;
    }
}

echo "Migration complete! Successfully updated $updatedCount asset tags.";
?>