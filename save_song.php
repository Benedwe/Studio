<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['song'])) {
    $user_id = $_SESSION['user_id'];

    if ($_FILES['song']['size'] > 10 * 1024 * 1024) {
        $message = "Audio file is too large. Maximum size is 10MB.";
    } elseif ($_FILES['song']['error'] === UPLOAD_ERR_OK) {
        $songData = file_get_contents($_FILES['song']['tmp_name']);
        $songName = $_FILES['song']['name'];
        $insertSongQuery = "INSERT INTO songs (user_id, song_name, song_data) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertSongQuery);
      
        $null = NULL;
        $stmt->bind_param("isb", $user_id, $songName, $null);
        $stmt->send_long_data(2, $songData);

        if ($stmt->execute()) {
            $message = "Song uploaded successfully!";
        } else {
            $message = "Failed to upload the song. Please try again.";
        }

        $stmt->close();
    } else {
        $message = "Error uploading the song.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Song</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 400px;
        }
        .container h2 {
            margin-bottom: 20px;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-top: 20px;
            color: green;
        }
        @media (max-width: 600px) {
            .container {
                width: 98vw;
                min-width: 0;
                padding: 12px 4px;
                border-radius: 0;
                box-shadow: none;
            }
            body {
                padding: 0;
                min-height: 100vh;
            }
            .container h2 {
                font-size: 1.2em;
            }
            input[type="file"], button {
                font-size: 1em;
                padding: 8px;
                width: 100%;
                box-sizing: border-box;
            }
            .message {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Your Song</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="song" accept="audio/*" required>
            <button type="submit">Upload Song</button>
        </form>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>