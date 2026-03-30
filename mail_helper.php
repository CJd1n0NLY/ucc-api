<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

function sendNotification($toEmail, $studentName, $type, $items = [], $link = null) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // smtp.hostinger.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'postradocharles.bsit@gmail.com'; // borrowing-admin@bsitfoura.com
        $mail->Password   = 'hmrf spnq unuf hdsn'; //WrXA3o=9
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 587; // 465

        $mail->setFrom('no-reply@ucc-borrowing.com', 'UCC Borrowing System');
        $mail->addAddress($toEmail, $studentName);
        $mail->isHTML(true);

        // Format the item list
        $itemList = "";
        if (!empty($items)) {
            $itemList = "<ul style='background-color:#f8fafc; padding:15px 15px 15px 35px; border-radius:6px; border:1px solid #e2e8f0; margin: 20px 0;'>";
            foreach($items as $i) { 
                $itemList .= "<li style='margin-bottom:8px; color:#334155;'><strong style='color:#0f172a;'>{$i['name']}</strong> <span style='color:#64748b; font-size:0.9em;'>(Tag: {$i['asset_tag']})</span></li>"; 
            }
            $itemList .= "</ul>";
        }

        // Initialize content variables
        $subject = "";
        $content = "";

        // Determine content based on type
        if ($type === 'VERIFY_EMAIL') {
            $subject = 'Verify your Student Portal Account';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <p style='color:#475569; line-height:1.6;'>Thank you for registering. Please confirm your email address to activate your account and gain access to the borrowing system catalog.</p>
                        <div style='text-align:center; margin:30px 0;'>
                            <a href='$link' style='display:inline-block; padding:12px 24px; background-color:#f97316; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:6px; letter-spacing:0.5px;'>Verify Email Address</a>
                        </div>
                        <p style='color:#64748b; font-size:12px; line-height:1.5;'>If the button doesn't work, copy and paste this link into your browser:<br><a href='$link' style='color:#3b82f6; word-break:break-all;'>$link</a></p>";
        }
        elseif ($type === 'BORROW') {
            $subject = 'Receipt: Items Borrowed';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <p style='color:#475569; line-height:1.6;'>This is a confirmation that you have successfully borrowed the following equipment:</p>
                        $itemList
                        <p style='color:#475569; line-height:1.6; font-weight:bold;'>Please ensure all items are returned on time and in good condition.</p>";
        } 
        elseif ($type === 'PARTIAL_BORROW') {
            $subject = 'Receipt: Partial Request Approved';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <p style='color:#475569; line-height:1.6;'>Your request has been processed. <strong style='color:#b45309;'>Please note that some items you requested are currently unavailable and have been unreserved.</strong></p>
                        <p style='color:#475569; line-height:1.6;'>You have successfully borrowed the following available items:</p>
                        $itemList
                        <p style='color:#475569; line-height:1.6; font-weight:bold;'>Please return them on time!</p>";
        }
        elseif ($type === 'RETURN') {
            $subject = 'Receipt: Items Returned';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <p style='color:#475569; line-height:1.6;'>We have successfully received your returned items:</p>
                        $itemList
                        <p style='color:#475569; line-height:1.6;'>Thank you for returning the equipment!</p>";
        }
        elseif ($type === 'REMINDER') {
            $subject = 'Reminder: Items Due Soon';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <div style='background-color:#fffbeb; border-left:4px solid #f59e0b; padding:15px; margin-bottom:20px;'>
                            <p style='color:#b45309; margin:0; font-weight:bold;'>This is a reminder to return your borrowed items TODAY by 8:00 PM.</p>
                        </div>
                        <p style='color:#475569; line-height:1.6;'>Pending items:</p>
                        $itemList";
        }
        elseif ($type === 'OVERDUE') {
            $subject = 'URGENT: Items Overdue (Past 8:00 PM)';
            $content = "<h3 style='color:#1e293b; margin-top:0;'>Hello $studentName,</h3>
                        <div style='background-color:#fef2f2; border-left:4px solid #ef4444; padding:15px; margin-bottom:20px;'>
                            <p style='color:#b91c1c; margin:0;'><strong>URGENT:</strong> The university closes at 9:00 PM. The following items missed the <strong>8:00 PM deadline</strong> and are now officially <strong>OVERDUE</strong>.</p>
                        </div>
                        $itemList
                        <p style='color:#b91c1c; line-height:1.6; font-weight:bold;'>Please return them immediately to the laboratory staff.</p>";
        }

        // Master HTML Template Wrapper
        $mail->Subject = $subject;
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0; padding:0; background-color:#f1f5f9; font-family:\"Segoe UI\", Arial, sans-serif;'>
            <div style='max-width:600px; margin:40px auto; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);'>
                
                <!-- Header -->
                <div style='background-color:#f97316; padding:25px 20px; text-align:center;'>
                    <h1 style='color:#ffffff; margin:0; font-size:24px; font-weight:600; letter-spacing:0.5px;'>University of Caloocan City</h1>
                    <p style='color:#ffedd5; margin:5px 0 0 0; font-size:15px;'>Equipment Borrowing System</p>
                </div>

                <!-- Main Content -->
                <div style='padding:40px 30px;'>
                    $content
                </div>

                <!-- Footer -->
                <div style='background-color:#f8fafc; padding:20px; text-align:center; border-top:1px solid #e2e8f0;'>
                    <p style='color:#94a3b8; font-size:12px; margin:0; line-height:1.5;'>
                        This is an automated message from the University of Caloocan City Equipment Borrowing System.<br>
                        Please do not reply directly to this email.
                    </p>
                </div>

            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>