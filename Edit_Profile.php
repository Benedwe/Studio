<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $imgData = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $updateQuery = "UPDATE users SET profile_pic = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("bi", $imgData, $user_id);
        $stmt->send_long_data(0, $imgData);
        if ($stmt->execute()) {
            $msg = "Profile picture updated!";
        } else {
            $msg = "Failed to update profile picture.";
        }
        $stmt->close();
    } else {
        $msg = "Error uploading image.";
    }
}


$query = "SELECT name, email, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($name, $email, $profile_pic);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile Picture</title>
    <style>
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
</head>
<body>
    <h2>Update Profile Picture</h2>
    <div>
        <?php if ($profile_pic): ?>
            <img class="profile-pic" src="data:image/jpeg;base64,<?php echo base64_encode($profile_pic); ?>" alt="Profile Picture">
        <?php else: ?>
            <img class="profile-pic" src="default_profile.png" alt="Profile Picture">
        <?php endif; ?>
        <form class="profile-upload-form" action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_pic" accept="image/*" required>
            <button type="submit">Change Profile Picture</button>
        </form>
        <?php if ($msg): ?>
            <div class="profile-msg"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>