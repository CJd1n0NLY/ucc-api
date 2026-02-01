<?php
$overdue_interval = "1 DAY"; 

$conn->query("UPDATE transactions 
              SET status = 'Overdue' 
              WHERE status = 'Active' 
              AND borrow_date < (NOW() - INTERVAL $overdue_interval)");

$conn->query("UPDATE items i
              INNER JOIN transactions t ON i.id = t.item_id
              SET i.status = 'Overdue'
              WHERE i.status = 'Borrowed' 
              AND t.status = 'Overdue'
              AND t.return_date IS NULL");
?>