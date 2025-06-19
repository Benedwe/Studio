<?php
include 'connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

function sendResetEmail($to, $reset_link) {
    $mail = new PHPMailer(true);
    try {
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'benjaedward23@gmail.com'; 
        $mail->Password   = 'your_app_password';    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('benjaedward23@gmail.com', 'Studio App');
        $mail->addAddress($to);

        
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset';
        $mail->Body    = "Click the link below to reset your password:<br><a href='$reset_link'>$reset_link</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));


    $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
    $stmt->bind_param("sss", $token, $expires, $email);
    $stmt->execute();

    $reset_link = "http://benjaedward23@gmail.com/reset_password.php?token=$token";
    sendResetEmail($email, $reset_link);

    echo "If your email is registered, a reset link has been sent.";
}
?>
<form method="POST">
    <input type="email" name="email" required placeholder="Enter your email">
    <button type="submit">Request Password Reset</button>
</form>