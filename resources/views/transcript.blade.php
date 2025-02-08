<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Whisper Transcription</title>
    </head>
    <body>
        <form action="{{ route('upload.audio') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h1>Upload Audio</h1> 
            <label for="audio">Upload Audio File:</label>
            <input type="file" name="audio" id="audio" required>
            <button type="submit">Transcribe</button>
        </form>

        <form id="audioForm" action="{{ route('upload.audio') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h1>Record and Upload Audio</h1> 
            <button type="button" id="startRecording">Start Recording</button> 
            <button type="button" id="stopRecording" disabled>Stop Recording</button>
            <button type="submit" id="uploadAudioButton" disabled>Upload Audio</button>
            <audio id="audioPlayback" controls></audio>
        </form>
        
    </body>

    @if(isset($jsonResult))
        <br><br>

        <h1>Extracted Data</h1>

        {{-- Reminder Section --}}
        @if(isset($jsonResult['reminder']))
            <h2>Reminder</h2>
            <p><strong>Title:</strong> {{ $jsonResult['reminder']['title'] }}</p>
            <p><strong>Date:</strong> {{ $jsonResult['reminder']['date'] }}</p>
            <p><strong>Task:</strong> {{ $jsonResult['reminder']['task'] }}</p>
            <p><strong>Recommendation:</strong> {{ $jsonResult['reminder']['recommendation'] }}</p>
        @endif

        {{-- Note Section --}}
        @if(isset($jsonResult['note']))
            <h2>Note</h2>
            <p><strong>Title:</strong> {{ $jsonResult['note']['title'] }}</p>
            <p><strong>Content:</strong> {{ $jsonResult['note']['content'] }}</p>
        @endif

        {{-- Call Section --}}
        @if(isset($jsonResult['call']))
            <h2>Call</h2>
            <p><strong>To:</strong> {{ $jsonResult['call']['to'] }}</p>
            <p><strong>Content:</strong> {{ $jsonResult['call']['content'] }}</p>
        @endif

        {{-- Summary Section --}}
        @if(isset($jsonResult['summary']))
            <h2>Summary</h2>
            <p><strong>Text:</strong> {{ $jsonResult['summary']['text'] }}</p>
        @endif

        {{-- Created At Timestamp --}}
        <p><strong>Created At:</strong> {{ $jsonResult['created_at'] }}</p>
    @endif

    <div id="contentContainer"></div>
    
</html>

<script> 
    document.addEventListener("DOMContentLoaded", function () {
        let mediaRecorder;
        let audioChunks = [];

        // Start recording
        document.getElementById('startRecording').addEventListener('click', () => {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = []; // Clear previous chunks
                    
                    mediaRecorder.start();
                    document.getElementById('startRecording').disabled = true;
                    document.getElementById('stopRecording').disabled = false;

                    mediaRecorder.addEventListener('dataavailable', event => {
                        audioChunks.push(event.data);
                    });

                    mediaRecorder.addEventListener('stop', () => {
                        // Convert recorded chunks into a Blob
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        const audioUrl = URL.createObjectURL(audioBlob);
                        
                        // Set audio source to the recorded blob for playback
                        const audio = document.getElementById('audioPlayback');
                        audio.src = audioUrl;
                        audio.controls = true; // Enable playback controls

                        // Enable the upload button
                        document.getElementById('uploadAudioButton').disabled = false;
                    });
                })
                .catch(error => {
                    console.error("Microphone access error:", error);
                    alert("Could not access microphone. Please check permissions.");
                });
        });

        // Stop recording
        document.getElementById('stopRecording').addEventListener('click', () => {
            if (mediaRecorder) {
                mediaRecorder.stop();
                document.getElementById('startRecording').disabled = false;
                document.getElementById('stopRecording').disabled = true;
            }
        });

        // Upload recorded audio
        document.getElementById('uploadAudioButton').addEventListener('click', (e) => {
            e.preventDefault();

            if (audioChunks.length === 0) {
                alert("No audio recorded!");
                return;
            }

            // Convert recorded chunks into a WAV file
            const audioBlob = new Blob(audioChunks, { type: "audio/wav" });
            const file = new File([audioBlob], "recording.wav", { type: "audio/wav" });

            // Prepare form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value); // CSRF token
            formData.append('audio', file);

            // Send audio file to server
            fetch("{{ route('upload.audio') }}", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Replace the page content dynamically
                    document.getElementById('contentContainer').innerHTML = `
                        <h1>Extracted Data</h1>

                        ${data.data.reminder ? `
                            <h2>Reminder</h2>
                            <p><strong>Title:</strong> ${data.data.reminder.title}</p>
                            <p><strong>Date:</strong> ${data.data.reminder.date}</p>
                            <p><strong>Task:</strong> ${data.data.reminder.task}</p>
                            <p><strong>Recommendation:</strong> ${data.data.reminder.recommendation}</p>
                        ` : ''}

                        ${data.data.note ? `
                            <h2>Note</h2>
                            <p><strong>Title:</strong> ${data.data.summary.title}</p>
                            <p><strong>Content:</strong> ${data.data.summary.content}</p>
                        ` : ''}

                        ${data.data.call ? `
                            <h2>Call</h2>
                            <p><strong>To:</strong> ${data.data.summary.to}</p>
                            <p><strong>Content:</strong> ${data.data.summary.content}</p>
                        ` : ''}

                        ${data.data.summary ? `
                            <h2>Summary</h2>
                            <p><strong>Text:</strong> ${data.data.summary.text}</p>
                        ` : ''}

                        <p><strong>Created At:</strong> ${data.data.created_at}</p>
                    `;
                } else {
                    alert("Error: " + JSON.stringify(data.errors));
                }
            })
            .catch(error => console.error('Upload error:', error));
        });

    });
</script> 