<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'connection.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die("Database connection error.");
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reward_message = "";
$reward_symbol = ""; 
$query = "SELECT COUNT(*) AS song_count FROM recordings WHERE user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Database query error.");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$song_count = $row['song_count'] ?? 0;


if ($song_count >= 50) {
    $reward_message = "Congratulations! You have recorded $song_count songs. You have earned a Platinum Reward!";
    $reward_symbol = "🥇"; 
} elseif ($song_count >= 30) {
    $reward_message = "Great job! You have recorded $song_count songs. You have earned a Gold Reward!";
    $reward_symbol = "🥈"; 
} elseif ($song_count >= 10) {
    $reward_message = "Well done! You have recorded $song_count songs. You have earned a Silver Reward!";
    $reward_symbol = "🥉";
} elseif ($song_count > 0) {
    $reward_message = "Keep going! You have recorded $song_count songs. Record more to earn rewards!";
    $reward_symbol = "🎵"; 
} else {
    $reward_message = "You haven't recorded any songs yet. Start recording to earn rewards!";
    $reward_symbol = "🎤"; 
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Rewards</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f2f5;
            color: #333;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .reward-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .reward-symbol {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .reward-message {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="reward-container">
        <div class="reward-symbol">
            <?php echo $reward_symbol; ?>
        </div>
        <div class="reward-message">
            <?php echo htmlspecialchars($reward_message); ?>
        </div>
        <a href="dashboard.php" class="back-link">Go Back to Dashboard</a>
    </div>
</body>
</html>