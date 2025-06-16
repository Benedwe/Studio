<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'connection.php';
$is_logged_in = isset($_SESSION['user_id']);


$profile_msg = "";
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $user_id = $_SESSION['user_id'];
    if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $imgData = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $updateQuery = "UPDATE users SET profile_pic = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("bi", $imgData, $user_id);
        $stmt->send_long_data(0, $imgData);
        if ($stmt->execute()) {
            $profile_msg = "Profile picture updated!";
        } else {
            $profile_msg = "Failed to update profile picture.";
        }
        $stmt->close();
    } else {
        $profile_msg = "Error uploading image.";
    }
}
$user_info = null;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT name, email, profile_pic FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($name, $email, $profile_pic);
    if ($stmt->fetch()) {
        $user_info = [
            'name' => $name,
            'email' => $email,
            'profile_pic' => $profile_pic
        ];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100vw;
            min-height: 100vh;
            width: auto;
            height: auto;
            z-index: -1;
            object-fit: cover;
        }
        .navbar {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255,255,255,0.95);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .tabs button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .tabs button:hover {
            background-color: #0056b3;
        }
        .tabs button.active {
            background-color: #0056b3;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        form {
            margin-top: 20px;
        }
        input, select, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .dashboard {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            background: #eee;
        }
        .profile-upload-form {
            margin-top: 10px;
        }
        .profile-msg {
            color: green;
            font-size: 1em;
            margin-top: 5px;
        }
    </style>
    <script>
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');

            const buttons = document.querySelectorAll('.tabs button');
            buttons.forEach(button => button.classList.remove('active'));
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
        }
    </script>
</head>
<body>
    <video id="bg-video" autoplay muted loop>
        <source src="studio.mp4" type="video/mp4">
    </video>
    <div class="navbar">
        <h1>Studio</h1>
        <?php if ($is_logged_in): ?>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($user_info['name'] ?? $_SESSION['Email']); ?>!</span>
                <a href="logout.php">Logout</a>
            </div>
        <?php else: ?>
            <div>
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="container">
        <div class="tabs">
            <button class="active" data-tab="tab-home" onclick="showTab('tab-home')">Home</button>
            <button data-tab="tab-record" onclick="showTab('tab-record')">Record Audio</button>
            <button data-tab="tab-chat" onclick="showTab('tab-chat')">Public Chat</button>
            <button data-tab="tab-songs" onclick="showTab('tab-songs')">My Songs</button>
        </div>
    
        <div id="tab-home" class="tab-content active">
            <h2>User Dashboard</h2>

            <?php if ($is_logged_in && $user_info): ?>
                <div class="dashboard">
                    <div>
                        <?php if ($user_info['profile_pic']): ?>
                            <img class="profile-pic" src="data:image/jpeg;base64,<?php echo base64_encode($user_info['profile_pic']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <img class="profile-pic" src="default_profile.png" alt="Profile Picture">
                        <?php endif; ?>
                        <form class="profile-upload-form" action="" method="POST" enctype="multipart/form-data">
                            <input type="file" name="profile_pic" accept="image/*" required>
                            <button type="submit">Change Profile Picture</button>
                        </form>
                        <?php if ($profile_msg): ?>
                            <div class="profile-msg"><?php echo htmlspecialchars($profile_msg); ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_info['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p>Please <a href="login.php">login</a> to view your dashboard.</p>
            <?php endif; ?>
        </div>
        <div id="tab-record" class="tab-content">
            <h2>Record Audio</h2>
            <form action="record_audio.php" method="POST">
                <label for="instrument">Select an instrument:</label>
                <select name="instrument" id="instrument" required>
                    <option value="guitar">Guitar</option>
                    <option value="piano">Piano</option>
                    <option value="drums">Drums</option>
                    <option value="violin">Violin</option>
                    <option value="flute">Flute</option>
                </select>
                <button type="submit">Start Recording</button>
            </form>
        </div>
        <div id="tab-chat" class="tab-content">
            <h2>Public Chat</h2>
            <form action="public_section.php" method="POST">
                <textarea name="message" rows="3" placeholder="Type your message here..." required></textarea>
                <button type="submit">Send Message</button>
            </form>
            <div>
                <h3>Messages:</h3>
                <?php
                $fetchMessagesQuery = "SELECT public_chat.message, users.name FROM public_chat JOIN users ON public_chat.user_id = users.id ORDER BY public_chat.created_at DESC";
                $messagesResult = $conn->query($fetchMessagesQuery);
                while ($row = $messagesResult->fetch_assoc()):
                ?>
                    <p><strong><?php echo htmlspecialchars($row['name']); ?>:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                <?php endwhile; ?>
            </div>
        </div>
        <div id="tab-songs" class="tab-content">
            <h2>My Songs</h2>
            <form action="save_song.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="song" accept="audio/*" required>
                <button type="submit">Upload Song</button>
            </form>
            <div>
                <h3>Your Songs:</h3>
                <?php
                $user_id = $_SESSION['user_id'] ?? 0;
                $fetchSongsQuery = "SELECT id, song_name FROM songs WHERE user_id = ?";
                $stmt = $conn->prepare($fetchSongsQuery);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $songsResult = $stmt->get_result();
                while ($row = $songsResult->fetch_assoc()):
                ?>
                    <p>
                        <strong><?php echo htmlspecialchars($row['song_name']); ?></strong>
                        <a href="play_audio.php?id=<?php echo $row['id']; ?>" target="_blank">Play</a>
                        
                    </p>
                    <a href = "Toptracks.php">Top Tracks</a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>