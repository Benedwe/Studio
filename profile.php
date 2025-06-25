<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .profile-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007BFF;
        }
        a:hover {
            text-decoration: underline;
        }
        p {
            font-size: 18px;
            margin: 10px 0;
        }
        .private-chat-miniapp {
            margin-top: 30px;
        }
        .chat-select {
            min-width: 150px;
        }
        .chat-form-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        .chat-form-row input[type="text"] {
            flex: 1;
        }
        .chat-form-row input[type="file"] {
            flex: 1;
        }
        #chatBox {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 8px;
            background: #fafafa;
        }
    </style>
</head>
<body>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Error: User not found.";
    $stmt->close();
    $conn->close();
    exit();
}
?>
    <div class="profile-container">
        <div style="text-align:center;">
            <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'default.png'; ?>"
                 alt="Profile Picture"
                 style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
            <div style="margin-top:10px;font-size:1.2em;font-weight:bold;">
                <?php echo htmlspecialchars($user['name']); ?>
            </div>
        </div>
        <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
        <a href="edit_profile.php">Edit Profile</a>
        <a href="logout.php">Logout</a>
        <hr>
        <div class="private-chat-miniapp">
            <h3>Private Chat</h3>
            <form id="chatForm" onsubmit="sendMessage(event)" enctype="multipart/form-data" action="private_chat_send.php" method="POST">
                <div class="chat-form-row">
                    <label for="chatUser">Chat with:</label>
                    <select id="chatUser" name="chatUser" class="chat-select" required>
                        <option value="">Select User</option>
                        <?php
                        require_once 'connection.php';
                        $users = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
                        while ($u = $users->fetch_assoc()) {
                            echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="chat-form-row">
                    <input type="text" id="chatMessage" name="chatMessage" placeholder="Type a message..." required>
                    <input type="file" id="chatImage" name="chatImage" accept="image/*">
                    <button type="submit">Send</button>
                </div>
            </form>
            <div id="chatBox"></div>
            <noscript>
                <div style="color:red;margin-top:10px;">
                    JavaScript is required for chat. You can also send messages directly via <a href="private_chat_send.php">private_chat_send.php</a>.
                </div>
            </noscript>
        </div>
        <script>
        let lastUser = '';
        function fetchMessages(userId) {
            if (!userId) {
                document.getElementById('chatBox').innerHTML = '';
                return;
            }
            fetch('private_chat_fetch.php?user_id=' + encodeURIComponent(userId))
                .then(res => res.text())
                .then(html => { document.getElementById('chatBox').innerHTML = html; });
        }
        document.getElementById('chatUser').addEventListener('change', function() {
            lastUser = this.value;
            fetchMessages(this.value);
        });
        function sendMessage(e) {
            e.preventDefault();
            const userId = document.getElementById('chatUser').value;
            const msg = document.getElementById('chatMessage').value.trim();
            const image = document.getElementById('chatImage').files[0];
            if (!userId || (!msg && !image)) return;
            let formData = new FormData();
            formData.append('receiver_id', userId);
            formData.append('message', msg);
            if (image) {
                formData.append('image', image);
            }
            fetch('private_chat_send.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                document.getElementById('chatMessage').value = '';
                document.getElementById('chatImage').value = '';
                fetchMessages(userId);
            });
        }
        setInterval(() => { if (lastUser) fetchMessages(lastUser); }, 5000);
        </script>
    </div>
</body>
</html>
