<?php
include 'db.php';
include 'mail_helper.php';

$messages = [];

// ==========================================
// 1. PROCESS OVERDUE ITEMS
// ==========================================
$query_overdue = "SELECT t.id as transaction_id, t.item_id, t.student_number, s.full_name, s.email, i.name, i.asset_tag
          FROM transactions t
          JOIN students s ON t.student_number = s.student_number
          JOIN items i ON t.item_id = i.id
          WHERE t.status = 'Active' AND t.due_date <= NOW() AND t.return_date IS NULL";

$result_overdue = $conn->query($query_overdue);

if ($result_overdue->num_rows > 0) {
    $overdue_by_student = [];
    $transaction_ids = [];
    $item_ids = [];

    while ($row = $result_overdue->fetch_assoc()) {
        $email = $row['email'];
        if (!isset($overdue_by_student[$email])) {
            $overdue_by_student[$email] = ['name' => $row['full_name'], 'items' => []];
        }
        $overdue_by_student[$email]['items'][] = ['name' => $row['name'], 'asset_tag' => $row['asset_tag']];
        
        $transaction_ids[] = $row['transaction_id']; 
        $item_ids[] = $row['item_id']; 
    }

    // Sends OVERDUE notification to each student
    foreach ($overdue_by_student as $email => $data) {
        if (!empty($email)) {
            sendNotification($email, $data['name'], 'OVERDUE', $data['items']);
        }
    }

    $t_ids_str = implode(',', $transaction_ids);
    $i_ids_str = implode(',', $item_ids);

    // Updates transaction and item statuses to Overdue
    $conn->query("UPDATE transactions SET status = 'Overdue' WHERE id IN ($t_ids_str)");
    $conn->query("UPDATE items SET status = 'Overdue' WHERE id IN ($i_ids_str)");

    $messages[] = "Overdue check: Processed " . count($transaction_ids) . " items.";
} else {
    $messages[] = "Overdue check: No items found.";
}


// ==========================================
// 2. PROCESS REMINDERS (1 Hour Before Due)
// ==========================================
// Selects items due within the next 1 hour that haven't had a reminder sent yet
$query_reminder = "SELECT t.id as transaction_id, s.full_name, s.email, i.name, i.asset_tag
          FROM transactions t
          JOIN students s ON t.student_number = s.student_number
          JOIN items i ON t.item_id = i.id
          WHERE t.status = 'Active' 
          AND t.due_date > NOW() 
          AND t.due_date <= DATE_ADD(NOW(), INTERVAL 1 HOUR) 
          AND t.return_date IS NULL
          AND t.reminder_sent = 0";

$result_reminder = $conn->query($query_reminder);

if ($result_reminder->num_rows > 0) {
    $reminder_by_student = [];
    $reminder_t_ids = [];

    while ($row = $result_reminder->fetch_assoc()) {
        $email = $row['email'];
        if (!isset($reminder_by_student[$email])) {
            $reminder_by_student[$email] = ['name' => $row['full_name'], 'items' => []];
        }
        $reminder_by_student[$email]['items'][] = ['name' => $row['name'], 'asset_tag' => $row['asset_tag']];
        $reminder_t_ids[] = $row['transaction_id'];
    }

    foreach ($reminder_by_student as $email => $data) {
        if (!empty($email)) {
            sendNotification($email, $data['name'], 'REMINDER', $data['items']);
        }
    }

    // Mark these specific transactions so they don't get reminder emails again
    $r_ids_str = implode(',', $reminder_t_ids);
    $conn->query("UPDATE transactions SET reminder_sent = 1 WHERE id IN ($r_ids_str)");

    $messages[] = "Reminder check: Processed " . count($reminder_t_ids) . " items.";
} else {
    $messages[] = "Reminder check: No items found.";
}

// Print results for the Cron Job logs
echo implode(" | ", $messages);
?>