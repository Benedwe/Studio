<?php
session_start();
include 'connection.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);
$query = "SELECT audio_data FROM public_audio WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($audioData);
$stmt->fetch();

if ($audioData) {
    header("Content-Type: audio/mpeg");
    echo $audioData;
} else {
    echo "Audio not found.";
}

$stmt->close();
$conn->close();
?>