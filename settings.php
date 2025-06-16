<?php
session_start();

$userSettings = [
    'username' => 'JohnDoe',
    'email' => 'johndoe@example.com',
    'audio_quality' => 'high',
    'notifications' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userSettings['username'] = $_POST['username'] ?? $userSettings['username'];
    $userSettings['email'] = $_POST['email'] ?? $userSettings['email'];
    $userSettings['audio_quality'] = $_POST['audio_quality'] ?? $userSettings['audio_quality'];
    $userSettings['notifications'] = isset($_POST['notifications']);
    
    $_SESSION['userSettings'] = $userSettings;
}

$currentSettings = $_SESSION['userSettings'] ?? $userSettings;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Page</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form { max-width: 400px; margin: auto; }
        label { display: block; margin: 10px 0 5px; }
        input[type="text"], input[type="email"], select {
            width: 100%; padding: 8px; margin-bottom: 10px;
        }
        input[type="submit"] {
            background-color: #4CAF50; color: white; border: none; padding: 10px 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h2>User Settings</h2>
<form method="POST">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentSettings['username']); ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentSettings['email']); ?>" required>

    <label for="audio_quality">Audio Quality:</label>
    <select id="audio_quality" name="audio_quality">
        <option value="low" <?php echo $currentSettings['audio_quality'] === 'low' ? 'selected' : ''; ?>>Low</option>
        <option value="medium" <?php echo $currentSettings['audio_quality'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
        <option value="high" <?php echo $currentSettings['audio_quality'] === 'high' ? 'selected' : ''; ?>>High</option>
    </select>

    <label for="notifications">Enable Notifications:</label>
    <input type="checkbox" id="notifications" name="notifications" <?php echo $currentSettings['notifications'] ? 'checked' : ''; ?>>

    <input type="submit" value="Save Settings">
</form>

</body>
</html>