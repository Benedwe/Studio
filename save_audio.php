<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'connection.php';

$response = ['success' => false];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['audio']) &&
    isset($_POST['user_id'])
) {
    $user_id = intval($_POST['user_id']);
    $audio = $_FILES['audio'];

    if ($audio['error'] === UPLOAD_ERR_OK) {
        $audioData = file_get_contents($audio['tmp_name']);
        $query = "INSERT INTO recordings (user_id, audio_data) VALUES (?, ?)";
        $stmt = $conn->prepare($query);

        $null = NULL;
        $stmt->bind_param("ib", $user_id, $null);
        $stmt->send_long_data(1, $audioData);

        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['error'] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['error'] = "File upload error.";
    }
} else {
    $response['error'] = "Invalid request.";
}

$conn->close();
echo json_encode($response);
?>

