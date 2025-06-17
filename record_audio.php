<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connection.php';

$message = "";
$redirect_url = "studio.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['instrument']) && isset($_POST['bitrate'])) {
        $selected_instrument = htmlspecialchars($_POST['instrument']);
        $selected_bitrate = htmlspecialchars($_POST['bitrate']);
        $message = "You selected the $selected_instrument with a bitrate of $selected_bitrate for recording. Interact with the instrument below.";
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Audio</title>
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
        .message-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .message {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .recording-controls {
            margin-top: 20px;
        }
        .instrument-button {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1em;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .instrument-button:hover {
            background-color: #0056b3;
        }
        .instrument-selector, .frequency-selector {
            margin-top: 20px;
        }
        .instrument-selector label, .frequency-selector label {
            font-size: 1em;
            margin-right: 10px;
        }
        .instrument-selector select, .frequency-selector select {
            padding: 5px;
            font-size: 1em;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="message <?php echo (strpos(strtolower($message), 'error:') !== false) ? 'error-message' : 'success-message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <div class="recording-controls">
            <h3>Microphone Recording</h3>
            <button id="start-mic-recording" class="instrument-button" onclick="startMicRecording()">Start Microphone Recording</button>
            <button id="stop-mic-recording" class="instrument-button" onclick="stopMicRecording()" disabled>Stop Microphone Recording</button>
            <p id="mic-recording-status">Microphone Status: Not Started</p>
        </div>

        <div class="instrument-selector">
            <label for="instrument">Select Instrument:</label>
            <select id="instrument" onchange="updateInstrument()">
                <option value="none">None</option>
                <option value="guitar">Guitar</option>
                <option value="piano">Piano</option>
                <option value="drums">Drums</option>
                <option value="violin">Violin</option>
            </select>
        </div>

        <div class="frequency-selector">
            <label for="frequency">Select Frequency:</label>
            <select id="frequency" onchange="updateFrequency()">
                <option value="440">440 Hz (Standard)</option>
                <option value="432">432 Hz</option>
                <option value="400">400 Hz</option>
                <option value="480">480 Hz</option>
            </select>
        </div>
    </div>

    <audio id="instrument-audio" loop></audio>

    <script>
        let micStream = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let selectedFrequency = 440;
        let selectedInstrument = "none"; 

        async function startMicRecording() {
            try {
                micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(micStream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    const audio = new Audio(audioUrl);
                    audio.play();
                    alert(`Recording complete. Playing back the recorded audio at ${selectedFrequency} Hz with ${selectedInstrument}.`);
                };

                mediaRecorder.start();
                document.getElementById('mic-recording-status').innerText = "Microphone Status: Recording...";
                document.getElementById('start-mic-recording').disabled = true;
                document.getElementById('stop-mic-recording').disabled = false;

                
                playInstrumentSound();
            } catch (error) {
                alert("Error accessing microphone: " + error.message);
            }
        }

        function stopMicRecording() {
            if (mediaRecorder && mediaRecorder.state === "recording") {
                mediaRecorder.stop();
                micStream.getTracks().forEach(track => track.stop());
                document.getElementById('mic-recording-status').innerText = "Microphone Status: Not Started";
                document.getElementById('start-mic-recording').disabled = false;
                document.getElementById('stop-mic-recording').disabled = true;

                stopInstrumentSound();
            }
        }

        function updateFrequency() {
            const frequencyDropdown = document.getElementById('frequency');
            selectedFrequency = frequencyDropdown.value;
            alert(`Frequency set to ${selectedFrequency} Hz.`);
        }

        function updateInstrument() {
            const instrumentDropdown = document.getElementById('instrument');
            selectedInstrument = instrumentDropdown.value;
            alert(`Instrument set to ${selectedInstrument}.`);
        }

        function playInstrumentSound() {
            const instrumentAudio = document.getElementById('instrument-audio');
            let audioSrc = "";

            switch (selectedInstrument) {
                case "guitar":
                    audioSrc = "sounds/guitar.mp3";
                    break;
                case "piano":
                    audioSrc = "sounds/piano.mp3";
                    break;
                case "drums":
                    audioSrc = "sounds/drums.mp3";
                    break;
                case "violin":
                    audioSrc = "sounds/violin.mp3";
                    break;
                default:
                    audioSrc = "";
            }

            if (audioSrc) {
                instrumentAudio.src = audioSrc;
                instrumentAudio.play();
            }
        }

        function stopInstrumentSound() {
            const instrumentAudio = document.getElementById('instrument-audio');
            instrumentAudio.pause();
            instrumentAudio.currentTime = 0;
        }
    </script>
</body>
</html>