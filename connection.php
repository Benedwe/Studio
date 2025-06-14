<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'Studio';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_pic LONGBLOB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($createUsersTable)) {
    error_log("Error creating users table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}
$createRecordingsTable = "
CREATE TABLE IF NOT EXISTS recordings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    audio_data LONGBLOB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($createRecordingsTable)) {
    error_log("Error creating recordings table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}

$createRewardsTable = "
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    reward_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (!$conn->query($createRewardsTable)) {
    error_log("Error creating rewards table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}
$createActivitiesTable = "
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (!$conn->query($createActivitiesTable)) {
    error_log("Error creating activities table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}

$createPublicChatTable = "
CREATE TABLE IF NOT EXISTS public_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($createPublicChatTable)) {
    error_log("Error creating public_chat table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}

$createPublicAudioTable = "
CREATE TABLE IF NOT EXISTS public_audio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    audio_data LONGBLOB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($createPublicAudioTable)) {
    error_log("Error creating public_audio table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}

$createSongsTable = "
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_name VARCHAR(255) NOT NULL,
    song_data LONGBLOB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($createSongsTable)) {
    error_log("Error creating songs table: " . $conn->error);
    die("Error setting up the database. Please try again later.");
}

error_log("All tables created successfully.");

?>
