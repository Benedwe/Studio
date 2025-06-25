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
        $mail->Username   = 'studiowww.co@gmail.com'; 
        $mail->Password   = 'Studioz@#12'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('studiowww.co@gmail.com', 'Studio');
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

    // Use your actual domain here, not an email address!
    $reset_link = "http://yourdomain.com/reset_password.php?token=$token";
    sendResetEmail($email, $reset_link);

    echo "If your email is registered, a reset link has been sent.";
}
?>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    min-height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}
.reset-container {
    background: #fff;
    padding: 24px 28px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    width: 350px;
    max-width: 98vw;
}
.reset-container h2 {
    text-align: center;
    margin-bottom: 18px;
}
.reset-container form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.reset-container input[type="email"] {
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 1em;
}
.reset-container button {
    padding: 10px;
    border-radius: 5px;
    border: none;
    background: #007bff;
    color: #fff;
    font-size: 1em;
    cursor: pointer;
    transition: background 0.2s;
}
.reset-container button:hover {
    background: #0056b3;
}
@media (max-width: 600px) {
    .reset-container {
        padding: 12px 4px;
        width: 98vw;
        border-radius: 0;
        box-shadow: none;
    }
    .reset-container h2 {
        font-size: 1.2em;
    }
    .reset-container input[type="email"],
    .reset-container button {
        font-size: 1em;
        padding: 8px;
    }
}
</style>
<div class="reset-container">
    <h2>Password Reset</h2>
    <form method="POST">
        <input type="email" name="email" required placeholder="Enter your email">
        <button type="submit">Request Password Reset</button>
    </form>
</div>