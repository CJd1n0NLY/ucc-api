<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

function sendNotification($toEmail, $studentName, $type, $items = []) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'postradocharles.bsit@gmail.com'; 
        $mail->Password   = 'hmrf spnq unuf hdsn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@ucc-borrowing.com', 'UCC Borrowing System');
        $mail->addAddress($toEmail, $studentName);

        $mail->isHTML(true);
        $itemList = "<ul>";
        foreach($items as $i) { $itemList .= "<li><strong>{$i['name']}</strong> (Tag: {$i['asset_tag']})</li>"; }
        $itemList .= "</ul>";

        if ($type === 'BORROW') {
            $mail->Subject = 'Receipt: Items Borrowed';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>You have successfully borrowed:</p>$itemList<p>Please return them on time!</p>";
        } 
        elseif ($type === 'RETURN') {
            $mail->Subject = 'Receipt: Items Returned';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>You have successfully returned:</p>$itemList<p>Thank you!</p>";
        }
        elseif ($type === 'REMINDER') {
            $mail->Subject = 'Reminder: Items Due Tomorrow';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>This is a reminder to return these items <strong>TOMORROW</strong>:</p>$itemList";
        }
        elseif ($type === 'OVERDUE') {
            $mail->Subject = 'URGENT: Items Overdue';
            $mail->Body    = "<h3 style='color:red;'>Hi $studentName,</h3><p>The following items are <strong>OVERDUE</strong>. Please return them immediately:</p>$itemList";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>