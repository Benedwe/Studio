<?php

session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


include 'connection.php';


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
$reward_message = "";
$song_count_query = "SELECT COUNT(*) AS song_count FROM recordings WHERE user_id = ?";
$song_stmt = $conn->prepare($song_count_query);
if ($song_stmt === false) {
    die("Database query error.");
}
$song_stmt->bind_param("i", $user_id);
$song_stmt->execute();
$song_result = $song_stmt->get_result();
$song_row = $song_result->fetch_assoc();
$song_count = $song_row['song_count'] ?? 0;

if ($song_count >= 50) {
    $reward_message = "Congratulations! You have recorded $song_count songs. You have earned a Platinum Reward!";
} elseif ($song_count >= 30) {
    $reward_message = "Great job! You have recorded $song_count songs. You have earned a Gold Reward!";
} elseif ($song_count >= 10) {
    $reward_message = "Well done! You have recorded $song_count songs. You have earned a Silver Reward!";
} elseif ($song_count > 0) {
    $reward_message = "Keep going! You have recorded $song_count songs. Record more to earn rewards!";
} else {
    $reward_message = "You haven't recorded any songs yet. Start recording to earn rewards!";
}

$song_stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        main, header, footer {
            max-width: 900px;
            margin: auto;
            padding: 16px;
        }
        header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            text-align: center;
        }
        nav ul {
            display: flex;
            gap: 18px;
            justify-content: center;
            padding: 0;
            margin: 0 0 16px 0;
            list-style: none;
            flex-wrap: wrap;
        }
        nav ul li {
            margin: 0;
        }
        nav ul li a {
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        nav ul li a:hover {
            background: #e6f0ff;
        }
        section {
            margin-bottom: 28px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            padding: 18px 14px;
        }
        .reward-message {
            font-size: 1.1em;
            color: #2d7a2d;
            background: #eafbe7;
            border-radius: 5px;
            padding: 10px;
            margin-top: 8px;
            text-align: center;
        }
        @media (max-width: 700px) {
            main, header, footer, section {
                max-width: 100vw;
                padding: 8px;
            }
            header h1 {
                font-size: 1.3em;
            }
            section {
                padding: 10px 4px;
            }
            nav ul {
                gap: 8px;
            }
            .reward-message {
                font-size: 1em;
                padding: 7px;
            }
        }
        @media (max-width: 480px) {
            header h1 {
                font-size: 1.1em;
            }
            nav ul {
                flex-direction: column;
                align-items: center;
                gap: 4px;
            }
            section {
                margin-bottom: 16px;
            }
            .reward-message {
                font-size: 0.98em;
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <nav>
            <ul>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Dashboard Overview</h2>
            <p>Here you can manage your account, view statistics, and more.</p>
        </section>

        <section>
            <h2>Recent Activities</h2>
            <ul>
                <li>Recording hisory</li>
                <li>Uploadfiles hisory</li>
                <li>Interactions</li>
                <li>Live streams</li>
            </ul>
        </section>

        <section>
            <h2>Loyalty Rewards</h2>
            <div class="reward-message">
                <?php echo htmlspecialchars($reward_message); ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Studio. All rights reserved.</p>
    </footer>
</body>
</html>