<?php
include 'db.php';
include 'mail_helper.php';

$query = "SELECT t.id as transaction_id, t.item_id, t.student_number, s.full_name, s.email, i.name, i.asset_tag
          FROM transactions t
          JOIN students s ON t.student_number = s.student_number
          JOIN items i ON t.item_id = i.id
          WHERE t.status = 'Active' AND t.due_date <= NOW() AND t.return_date IS NULL";

$result = $conn->query($query);

$overdue_by_student = [];
$transaction_ids = [];
$item_ids = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        if (!isset($overdue_by_student[$email])) {
            $overdue_by_student[$email] = [
                'name' => $row['full_name'],
                'items' => []
            ];
        }
        $overdue_by_student[$email]['items'][] = [
            'name' => $row['name'],
            'asset_tag' => $row['asset_tag']
        ];

        $transaction_ids[] = $row['transaction_id'];
        $item_ids[] = $row['item_id'];
    }

    foreach ($overdue_by_student as $email => $data) {
        if (!empty($email)) {
            sendNotification($email, $data['name'], 'OVERDUE', $data['items']);
        }
    }

    $t_ids_str = implode(',', $transaction_ids);
    $i_ids_str = implode(',', $item_ids);

    $conn->query("UPDATE transactions SET status = 'Overdue' WHERE id IN ($t_ids_str)");
    $conn->query("UPDATE items SET status = 'Overdue' WHERE id IN ($i_ids_str)");

    echo "Success: Processed " . count($transaction_ids) . " overdue items and sent notifications.";
} else {
    echo "No overdue items found at this time.";
}
?>