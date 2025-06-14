<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle chat message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_message') {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $insertMessageQuery = "INSERT INTO public_chat (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($insertMessageQuery);
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle audio upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_audio') {
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $audioData = file_get_contents($_FILES['audio']['tmp_name']);
        $insertAudioQuery = "INSERT INTO public_audio (user_id, audio_data) VALUES (?, ?)";
        $stmt = $conn->prepare($insertAudioQuery);
        $stmt->bind_param("ib", $user_id, $audioData);
        $stmt->send_long_data(1, $audioData);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch chat messages
$fetchMessagesQuery = "SELECT public_chat.message, users.name FROM public_chat JOIN users ON public_chat.user_id = users.id ORDER BY public_chat.created_at DESC";
$messagesResult = $conn->query($fetchMessagesQuery);

// Fetch shared audio
$fetchAudioQuery = "SELECT public_audio.id, users.name FROM public_audio JOIN users ON public_audio.user_id = users.id ORDER BY public_audio.created_at DESC";
$audioResult = $conn->query($fetchAudioQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Section</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        .chat-section, .audio-section {
            margin-top: 20px;
        }
        .chat-messages, .audio-list {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .chat-messages p, .audio-list p {
            margin: 5px 0;
        }
        form {
            margin-top: 10px;
        }
        textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Public Section</h2>

        <!-- Chat Section -->
        <div class="chat-section">
            <h3>Public Chat</h3>
            <div class="chat-messages">
                <?php while ($row = $messagesResult->fetch_assoc()): ?>
                    <p><strong><?php echo htmlspecialchars($row['name']); ?>:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                <?php endwhile; ?>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="post_message">
                <textarea name="message" rows="3" placeholder="Type your message here..." required></textarea>
                <button type="submit">Send</button>
            </form>
        </div>

        <!-- Audio Sharing Section -->
        <div class="audio-section">
            <h3>Shared Audio</h3>
            <div class="audio-list">
                <?php while ($row = $audioResult->fetch_assoc()): ?>
                    <p>
                        <strong><?php echo htmlspecialchars($row['name']); ?>:</strong>
                        <a href="play_audio.php?id=<?php echo $row['id']; ?>" target="_blank">Play Audio</a>
                    </p>
                <?php endwhile; ?>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_audio">
                <input type="file" name="audio" accept="audio/*" required>
                <button type="submit">Upload Audio</button>
            </form>
        </div>
    </div>
</body>
</html>