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
        @media (max-width: 900px) {
    .container {
        max-width: 98vw;
        padding: 10px;
    }
    .dashboard {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    .tabs {
        flex-direction: column;
        gap: 8px;
    }
    .tabs button {
        width: 100%;
        margin-bottom: 5px;
    }
    #visualizer {
        width: 98vw !important;
        max-width: 100%;
        height: 80px !important;
    }
}
@media (max-width: 600px) {
    body, html {
        padding: 0;
    }
    .container {
        border-radius: 0;
        box-shadow: none;
        padding: 5px;
    }
    .navbar {
        flex-direction: column;
        align-items: flex-start;
        padding: 8px;
    }
    .dashboard {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    .profile-pic {
        width: 80px;
        height: 80px;
    }
    #visualizer {
        width: 98vw !important;
        max-width: 100%;
        height: 50px !important;
    }
    .tabs button {
        font-size: 1em;
        padding: 8px;
    }
    input, select, textarea, button {
        font-size: 1em;
        padding: 8px;
    }
}

@media (max-width: 700px) {
    .private-messages-dashboard img {
        max-width: 80vw !important;
        height: auto !important;
    }
    .private-messages-dashboard {
        font-size: 1em;
        padding: 8px 2px;
    }
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
          
            <button data-tab="tab-live" onclick="showTab('tab-live')">Go Live</button>
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
                <p>
                    <a href="Toptracks.php" style="color:#007bff; font-weight:bold; text-decoration:underline;">
                        🎵 View Top Tracks
                    </a>
                </p>
                <div class="private-messages-dashboard" style="margin-top:30px;">
                    <h3>Your Recent Private Messages</h3>
                    <div style="max-height:200px;overflow-y:auto;">
                    <?php
                    require_once 'connection.php';
                    $uid = $_SESSION['user_id'];
                    $stmt = $conn->prepare(
                        "SELECT pm.message, pm.image_path, u.name, pm.created_at
                         FROM private_messages pm
                         JOIN users u ON pm.sender_id = u.id
                         WHERE pm.receiver_id = ?
                         ORDER BY pm.created_at DESC
                         LIMIT 10"
                    );
                    $stmt->bind_param("i", $uid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows > 0) {
                        while ($msg = $res->fetch_assoc()) {
                            echo '<div style="margin-bottom:10px;">';
                            echo '<strong>' . htmlspecialchars($msg['name']) . ':</strong> ';
                            echo htmlspecialchars($msg['message']);
                            if ($msg['image_path']) {
                                echo '<br><img src="' . htmlspecialchars($msg['image_path']) . '" alt="Image" style="max-width:120px;max-height:120px;border-radius:8px;margin-top:4px;">';
                            }
                            echo '<div style="font-size:0.85em;color:#888;">' . htmlspecialchars($msg['created_at']) . '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div>No private messages yet.</div>';
                    }
                    $stmt->close();
                    ?>
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
                <?php endwhile; ?>
            </div>
        </div>
        <div id="tab-live" class="tab-content">
            composer --version            <h2>Go Live with YouTube</h2>
            <form method="POST">
                <label for="youtube_id">Enter Your YouTube Live Video ID:</label>
                <input type="text" id="youtube_id" name="youtube_id" placeholder="e.g. dQw4w9WgXcQ" required>
                <button type="submit">Show My Live Stream</button>
            </form>
            <?php if (!empty($_POST['youtube_id'])): ?>
                <div style="margin-top:20px;">
                    <iframe width="560" height="315"
                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($_POST['youtube_id']); ?>?autoplay=1"
                        frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>
                    </iframe>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin:20px 0;">
            <h3>Audio effects</h3>
            <input type="file" id="audioFile" accept="audio/*">
            <button onclick="playAudio()">Play</button>
            <button onclick="pauseAudio()">Pause</button>
            <br>
            <label><input type="checkbox" id="reverbToggle" onchange="toggleReverb()"> Reverb</label>
            <label><input type="checkbox" id="delayToggle" onchange="toggleDelay()"> Delay</label>
            <label><input type="checkbox" id="distortionToggle" onchange="toggleDistortion()"> Distortion</label>
            <label>
                Low-pass Filter:
                <input type="range" id="filterSlider" min="500" max="20000" value="20000" step="100" oninput="updateFilter(this.value)">
                <span id="filterValue">20000</span> Hz
            </label>
            <label>
                Panning:
                <input type="range" id="panSlider" min="-1" max="1" value="0" step="0.01" oninput="updatePan(this.value)">
                <span id="panValue">0</span>
            </label>
            <label>
                Speed:
                <input type="range" id="speedSlider" min="0.5" max="2" value="1" step="0.01" oninput="updateSpeed(this.value)">
                <span id="speedValue">1</span>x
            </label>
            <br>
          
            <label>
                <input type="checkbox" id="noiseReductionToggle" onchange="toggleNoiseReduction()"> Noise Reduction
            </label>
            <audio id="audioElement" controls style="margin-top:10px;"></audio>
            <canvas id="visualizer" width="600" height="120" style="background:#222;display:block;margin:20px auto 0;border-radius:8px;max-width:100%;"></canvas>
        </div>
        <script>
        let audioCtx, source, audioElement, track;
        let convolver, delay, distortion, filter, panner, analyser;
        let reverbEnabled = false, delayEnabled = false, distortionEnabled = false;
        let animationId;

        let noiseReductionEnabled = false;
        let noiseGate;

        document.getElementById('audioFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                audioElement = document.getElementById('audioElement');
                audioElement.src = url;
                audioElement.load();
                setupWebAudio();
            }
        });

        function setupWebAudio() {
            if (audioCtx) audioCtx.close();
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            audioElement = document.getElementById('audioElement');
            track = audioCtx.createMediaElementSource(audioElement);

         
            convolver = audioCtx.createConvolver();
            let impulse = audioCtx.createBuffer(2, 0.5 * audioCtx.sampleRate, audioCtx.sampleRate);
            for (let i = 0; i < impulse.numberOfChannels; i++) {
                let channelData = impulse.getChannelData(i);
                for (let j = 0; j < channelData.length; j++) {
                    channelData[j] = (Math.random() * 2 - 1) * Math.pow(1 - j / channelData.length, 2);
                }
            }
            convolver.buffer = impulse;

          
            delay = audioCtx.createDelay(5.0);
            delay.delayTime.value = 0.3;

        
            distortion = audioCtx.createWaveShaper();
            distortion.curve = makeDistortionCurve(400);
            distortion.oversample = '4x';

            filter = audioCtx.createBiquadFilter();
            filter.type = "lowpass";
            filter.frequency.value = 20000;

            panner = audioCtx.createStereoPanner();
            panner.pan.value = 0;

          
            analyser = audioCtx.createAnalyser();
            analyser.fftSize = 256;

        
            noiseGate = audioCtx.createDynamicsCompressor();
            noiseGate.threshold.value = -50;
            noiseGate.knee.value = 40;
            noiseGate.ratio.value = 12;
            noiseGate.attack.value = 0;
            noiseGate.release.value = 0.25;

            connectNodes();
            visualize();
        }

        function connectNodes() {
          
            if (track) track.disconnect();
            if (convolver) convolver.disconnect();
            if (delay) delay.disconnect();
            if (distortion) distortion.disconnect();
            if (filter) filter.disconnect();
            if (panner) panner.disconnect();
            if (analyser) analyser.disconnect();

            let node = track;
            if (reverbEnabled) {
                node.connect(convolver);
                node = convolver;
            }
            if (delayEnabled) {
                node.connect(delay);
                node = delay;
            }
            if (distortionEnabled) {
                node.connect(distortion);
                node = distortion;
            }
            node.connect(filter);
            filter.connect(panner);
            panner.connect(analyser);
            analyser.connect(audioCtx.destination);

            // Connect to noise gate if enabled
            if (noiseReductionEnabled) {
                analyser.connect(noiseGate);
                noiseGate.connect(audioCtx.destination);
            }
        }

        function playAudio() {
            if (audioElement) {
                audioElement.play();
                if (audioCtx && audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
            }
        }

        function pauseAudio() {
            if (audioElement) {
                audioElement.pause();
            }
        }

        function toggleReverb() {
            reverbEnabled = document.getElementById('reverbToggle').checked;
            connectNodes();
        }
        function toggleDelay() {
            delayEnabled = document.getElementById('delayToggle').checked;
            connectNodes();
        }
        function toggleDistortion() {
            distortionEnabled = document.getElementById('distortionToggle').checked;
            connectNodes();
        }
        function toggleNoiseReduction() {
            noiseReductionEnabled = document.getElementById('noiseReductionToggle').checked;
            connectNodes();
        }
        function updateFilter(val) {
            if (filter) filter.frequency.value = val;
            document.getElementById('filterValue').textContent = val;
        }
        function updatePan(val) {
            if (panner) panner.pan.value = val;
            document.getElementById('panValue').textContent = val;
        }
        function updateSpeed(val) {
            if (audioElement) audioElement.playbackRate = val;
            document.getElementById('speedValue').textContent = val;
        }
        function makeDistortionCurve(amount) {
            let k = typeof amount === 'number' ? amount : 50,
                n_samples = 44100,
                curve = new Float32Array(n_samples),
                deg = Math.PI / 180;
            for (let i = 0; i < n_samples; ++i) {
                let x = i * 2 / n_samples - 1;
                curve[i] = (3 + k) * x * 20 * deg / (Math.PI + k * Math.abs(x));
            }
            return curve;
        }

        function visualize() {
            const canvas = document.getElementById('visualizer');
            const ctx = canvas.getContext('2d');
            const WIDTH = canvas.width;
            const HEIGHT = canvas.height;
            if (!analyser) return;

            function draw() {
                animationId = requestAnimationFrame(draw);
                let dataArray = new Uint8Array(analyser.frequencyBinCount);
                analyser.getByteFrequencyData(dataArray);

                ctx.fillStyle = '#222';
                ctx.fillRect(0, 0, WIDTH, HEIGHT);

                let barWidth = (WIDTH / dataArray.length) * 2.5;
                let barHeight;
                let x = 0;

                for (let i = 0; i < dataArray.length; i++) {
                    barHeight = dataArray[i];
                    ctx.fillStyle = 'rgb(' + (barHeight+100) + ',50,200)';
                    ctx.fillRect(x, HEIGHT - barHeight/2, barWidth, barHeight/2);
                    x += barWidth + 1;
                }
            }
            draw();
        }
        </script>
    </div>
</body>
</html>