<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

function sendNotification($toEmail, $studentName, $type, $items = [], $link = null) {
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

        if ($type === 'VERIFY_EMAIL') {
            $mail->Subject = 'Verify your UCC Student Portal Account';
            $mail->Body    = "<h3>Hi $studentName,</h3>
                              <p>Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
                              <p><a href='$link' style='display:inline-block;padding:10px 20px;background:#f97316;color:#fff;text-decoration:none;border-radius:5px;'>Verify Email</a></p>
                              <p>If the button doesn't work, copy and paste this link into your browser:<br>$link</p>";
        }
        
        elseif ($type === 'BORROW') {
            $mail->Subject = 'Receipt: Items Borrowed';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>You have successfully borrowed:</p>$itemList<p>Please return them on time!</p>";
        } 
        elseif ($type === 'PARTIAL_BORROW') {
            $mail->Subject = 'Receipt: Partial Request Approved';
            $mail->Body    = "<h3>Hi $studentName,</h3>
                              <p>Your request has been processed. <strong>Please note that some items you requested were currently unavailable and have been unreserved.</strong></p>
                              <p>You have successfully borrowed the following available items:</p>
                              $itemList
                              <p>Please return them on time!</p>";
        }
        elseif ($type === 'RETURN') {
            $mail->Subject = 'Receipt: Items Returned';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>You have successfully returned:</p>$itemList<p>Thank you!</p>";
        }
        elseif ($type === 'REMINDER') {
            $mail->Subject = 'Reminder: Items Due Soon';
            $mail->Body    = "<h3>Hi $studentName,</h3><p>This is a reminder to return your borrowed items <strong>TODAY by 8:00 PM</strong>:</p>$itemList";
        }
        elseif ($type === 'OVERDUE') {
            $mail->Subject = 'URGENT: Items Overdue (Past 8:00 PM)';
            $mail->Body    = "<h3 style='color:red;'>Hi $studentName,</h3>
                              <p>The university closes at 9:00 PM. The following items missed the <strong>8:00 PM deadline</strong> and are now officially <strong>OVERDUE</strong>.</p>
                              $itemList
                              <p style='color:red;'><strong>Please return them immediately to the laboratory staff.</strong></p>";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>