<?php
session_start();
require 'connection.php';

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);
$image_path = null;

if (isset($_FILES['chatImage']) && $_FILES['chatImage']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['chatImage']['name'], PATHINFO_EXTENSION);
    $targetDir = "uploads/private_chat/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $filename = uniqid('img_', true) . '.' . $ext;
    $targetFile = $targetDir . $filename;
    if (move_uploaded_file($_FILES['chatImage']['tmp_name'], $targetFile)) {
        $image_path = $targetFile;
    }
}

if ($receiver_id && ($message || $image_path)) {
    $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $image_path);
    $stmt->execute();
    $stmt->close();
}
$conn->close();