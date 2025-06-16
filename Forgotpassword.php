<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));


    $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
    $stmt->bind_param("sss", $token, $expires, $email);
    $stmt->execute();

    $reset_link = "http://benjaedward23@gmail.com/reset_password.php?token=$token";
    mail($email, "Password Reset", "Click to reset your password: $reset_link");

    echo "If your email is registered, a reset link has been sent.";
}
?>
<form method="POST">
    <input type="email" name="email" required placeholder="Enter your email">
    <button type="submit">Request Password Reset</button>
</form>