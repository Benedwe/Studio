<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


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


// Handle follow/unfollow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'follow' && isset($_POST['followed_id'])) {
        $followed_id = intval($_POST['followed_id']);
        if ($followed_id !== $user_id) {
            $stmt = $conn->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $followed_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($_POST['action'] === 'unfollow' && isset($_POST['followed_id'])) {
        $followed_id = intval($_POST['followed_id']);
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->bind_param("ii", $user_id, $followed_id);
        $stmt->execute();
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_private_message') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id']);
    $private_message = trim($_POST['private_message']);
    if ($receiver_id && !empty($private_message)) {
        $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $private_message);
        $stmt->execute();
        $stmt->close();
    }
}

$fetchMessagesQuery = "SELECT public_chat.message, users.name FROM public_chat JOIN users ON public_chat.user_id = users.id ORDER BY public_chat.created_at DESC";
$messagesResult = $conn->query($fetchMessagesQuery);


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
        .chat-section, .audio-section, .users-section, .inbox-section {
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
        textarea, input[type="file"], select {
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
        .inbox-section {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .inbox-section textarea {
            width: 100%;
            margin-top: 5px;
        }
        .inbox-section select {
            margin-bottom: 5px;
            padding: 5px;
        }
        .private-messages-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 8px;
        }
        @media (max-width: 900px) {
    .container {
        max-width: 99vw;
        padding: 8px;
        border-radius: 0;
        box-shadow: none;
    }
    .chat-messages, .audio-list, .private-messages-list {
        max-height: 180px;
        font-size: 1em;
    }
    .inbox-section, .audio-section, .chat-section, .users-section {
        padding: 8px 2px;
        margin-top: 14px;
    }
    textarea, input[type="file"], select {
        font-size: 1em;
        padding: 8px;
    }
    button {
        font-size: 1em;
        padding: 8px 0;
        width: 100%;
        margin-top: 6px;
    }
    ul {
        padding-left: 18px;
    }
}
@media (max-width: 600px) {
    .container {
        max-width: 100vw;
        padding: 2px;
        border-radius: 0;
    }
    h2, h3, h4 {
        font-size: 1.1em;
    }
    .chat-messages, .audio-list, .private-messages-list {
        max-height: 120px;
        font-size: 0.98em;
        padding: 4px;
    }
    .inbox-section, .audio-section, .chat-section, .users-section {
        padding: 4px 0;
        margin-top: 10px;
    }
    textarea, input[type="file"], select {
        font-size: 0.98em;
        padding: 6px;
    }
    button {
        font-size: 0.98em;
        padding: 7px 0;
        width: 100%;
        margin-top: 5px;
    }
    ul {
        padding-left: 12px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h2>Public Section</h2>

        <div class="users-section">
            <h3>Users</h3>
            <ul>
                <?php
                $user_id = $_SESSION['user_id'];
                $usersResult = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
                $followingResult = $conn->query("SELECT followed_id FROM follows WHERE follower_id = $user_id");
                $following = [];
                while ($row = $followingResult->fetch_assoc()) {
                    $following[] = $row['followed_id'];
                }
                while ($user = $usersResult->fetch_assoc()):
                ?>
                    <li>
                        <?php echo htmlspecialchars($user['name']); ?>
                        <?php if (in_array($user['id'], $following)): ?>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="unfollow">
                                <input type="hidden" name="followed_id" value="<?php echo $user['id']; ?>">
                                <button type="submit">Unfollow</button>
                            </form>
                        <?php else: ?>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="follow">
                                <input type="hidden" name="followed_id" value="<?php echo $user['id']; ?>">
                                <button type="submit">Follow</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

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

        <div class="inbox-section">
            <h3>Inbox (Private Messages)</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="send_private_message">
                <label for="receiver_id">Send to:</label>
                <select name="receiver_id" required>
                    <option value="">Select User</option>
                    <?php
                    $usersResult2 = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
                    while ($user = $usersResult2->fetch_assoc()):
                    ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <textarea name="private_message" rows="2" placeholder="Type your private message..." required></textarea>
                <button type="submit">Send</button>
            </form>
            <div class="private-messages-list" style="margin-top:15px;">
                <h4>Your Conversations</h4>
                <?php
                // Fetch all private messages involving the current user
                $pmQuery = "
                    SELECT pm.*, u1.name AS sender_name, u2.name AS receiver_name
                    FROM private_messages pm
                    JOIN users u1 ON pm.sender_id = u1.id
                    JOIN users u2 ON pm.receiver_id = u2.id
                    WHERE pm.sender_id = $user_id OR pm.receiver_id = $user_id
                    ORDER BY pm.created_at DESC
                    LIMIT 30
                ";
                $pmResult = $conn->query($pmQuery);
                while ($pm = $pmResult->fetch_assoc()):
                    $isSent = $pm['sender_id'] == $user_id;
                ?>
                    <div style="margin-bottom:8px;">
                        <strong><?php echo $isSent ? 'To ' . htmlspecialchars($pm['receiver_name']) : 'From ' . htmlspecialchars($pm['sender_name']); ?>:</strong>
                        <?php echo htmlspecialchars($pm['message']); ?>
                        <span style="color:#888;font-size:0.9em;">(<?php echo $pm['created_at']; ?>)</span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <a href="TopTracks.php">Toptracks</a>
    
</body>
</html>