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
        #visualizer {
            background: #222;
            display: block;
            margin: 20px auto 0;
            border-radius: 8px;
        }
        @media (max-width: 700px) {
            .message-container {
                max-width: 98vw;
                padding: 15px 5px;
                border-radius: 0;
                box-shadow: none;
            }
            .recording-controls, .instrument-selector, .frequency-selector {
                margin-top: 10px;
            }
            #visualizer {
                width: 98vw !important;
                max-width: 100%;
                height: 70px !important;
            }
            .instrument-button {
                width: 100%;
                margin: 8px 0;
                font-size: 1em;
                padding: 10px 0;
            }
            select, input[type="range"], input[type="checkbox"], label {
                font-size: 1em;
            }
        }
        @media (max-width: 480px) {
            body, html {
                padding: 0;
            }
            .message-container {
                padding: 8px 2px;
            }
            #visualizer {
                width: 98vw !important;
                max-width: 100%;
                height: 40px !important;
            }
            .instrument-button {
                font-size: 0.95em;
                padding: 8px 0;
            }
        }
    </style>
    <!-- PitchShift.js for pitch control -->
    <script src="https://cdn.jsdelivr.net/npm/pitchshift@latest/dist/pitchshift.min.js"></script>
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

        <label>
            <input type="checkbox" id="noiseReductionToggle" onchange="toggleNoiseReduction()"> Noise Reduction
        </label>

        <label>
            Pitch:
            <input type="range" id="pitchSlider" min="0.5" max="2" value="1" step="0.01" oninput="updatePitch(this.value)">
            <span id="pitchValue">1</span>x
        </label>
    </div>

    <audio id="instrument-audio" loop></audio>
    <audio id="audioElement" style="display:none;"></audio>
    <canvas id="visualizer" width="600" height="120"></canvas>

    <script>
        let micStream = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let selectedFrequency = 440;
        let selectedInstrument = "none";
        let audioCtx, audioElement, track, noiseGate, pitchShiftNode, analyser;
        let noiseReductionEnabled = false;
        let pitchValue = 1;
        let animationId;

        function updateInstrument() {
            const instrumentDropdown = document.getElementById('instrument');
            selectedInstrument = instrumentDropdown.value;
            alert(`Instrument set to ${selectedInstrument}.`);
        }

        function updateFrequency() {
            const frequencyDropdown = document.getElementById('frequency');
            selectedFrequency = frequencyDropdown.value;
            alert(`Frequency set to ${selectedFrequency} Hz.`);
        }

        function updatePitch(value) {
            pitchValue = value;
            document.getElementById('pitchValue').textContent = value;
            if (pitchShiftNode) {
                let semitones = 12 * Math.log2(value);
                pitchShiftNode.transpose = semitones;
            }
        }

        function toggleNoiseReduction() {
            noiseReductionEnabled = document.getElementById('noiseReductionToggle').checked;
            connectNodes();
        }

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
                    audioElement = document.getElementById('audioElement');
                    audioElement.src = audioUrl;
                    audioElement.load();
                    setupWebAudio();
                    audioElement.onplay = () => {
                        if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
                        visualize();
                    };
                    audioElement.onpause = () => cancelAnimationFrame(animationId);
                    audioElement.play();
                    alert(`Recording complete. Playing back the recorded audio at ${selectedFrequency} Hz with ${selectedInstrument}.`);

                    // --- SEND AUDIO TO SERVER ---
                    const formData = new FormData();
                    formData.append('audio', audioBlob, 'recording.wav');
                    formData.append('user_id', <?php echo json_encode($_SESSION['user_id']); ?>);

                    fetch('save_audio.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Audio saved to database!');
                        } else {
                            alert('Error saving audio: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('Error uploading audio: ' + error);
                    });
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

        function playInstrumentSound() {
            const instrumentAudio = document.getElementById('instrument-audio');
            let audioSrc = "";
            switch (selectedInstrument) {
                case "guitar": audioSrc = "sounds/guitar.mp3"; break;
                case "piano": audioSrc = "sounds/piano.mp3"; break;
                case "drums": audioSrc = "sounds/drums.mp3"; break;
                case "violin": audioSrc = "sounds/violin.mp3"; break;
                default: audioSrc = "";
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

        function setupWebAudio() {
            if (audioCtx) audioCtx.close();
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            audioElement = document.getElementById('audioElement');
            track = audioCtx.createMediaElementSource(audioElement);

            // Noise Gate (simple noise reduction using dynamics compressor)
            noiseGate = audioCtx.createDynamicsCompressor();
            noiseGate.threshold.value = -50;
            noiseGate.knee.value = 40;
            noiseGate.ratio.value = 12;
            noiseGate.attack.value = 0;
            noiseGate.release.value = 0.25;

            // PitchShift node
            pitchShiftNode = new window.PitchShift(audioCtx);
            pitchShiftNode.transpose = 12 * Math.log2(pitchValue);

            // Analyser for visualization
            analyser = audioCtx.createAnalyser();
            analyser.fftSize = 256;

            connectNodes();
        }

        function connectNodes() {
            if (!track) return;
            track.disconnect();
            if (pitchShiftNode) pitchShiftNode.disconnect();
            if (noiseGate) noiseGate.disconnect();
            if (analyser) analyser.disconnect();

            // Connect the audio graph: track -> pitchShift -> (noiseGate) -> analyser -> destination
            let node = track;
            node.connect(pitchShiftNode.input);
            if (noiseReductionEnabled) {
                pitchShiftNode.connect(noiseGate);
                noiseGate.connect(analyser);
            } else {
                pitchShiftNode.connect(analyser);
            }
            analyser.connect(audioCtx.destination);
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
</body>
</html>