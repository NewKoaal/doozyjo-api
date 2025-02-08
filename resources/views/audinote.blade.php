<!DOCTYPE html> 

<html lang="en"> 

<head> 

    <meta charset="UTF-8"> 

    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 

    <title>Voice Recording</title> 

    <link rel="stylesheet" href="{{ asset('css/style.css') }}"> 

</head> 

<body> 

    <h1>Record and Upload Audio</h1> 

    <button id="startRecording">Start Recording</button> 

    <button id="stopRecording" disabled>Stop Recording</button> 

    <button id="goToUploads">View Uploaded Files</button> 

    <audio id="audioPlayback" controls></audio> 

    <script> 

        let mediaRecorder; 

        let audioChunks = []; 

        document.getElementById('startRecording').addEventListener('click', () => { 

            navigator.mediaDevices.getUserMedia({ audio: true }) 

                .then(stream => { 

                    mediaRecorder = new MediaRecorder(stream); 

                    mediaRecorder.start(); 

                    document.getElementById('startRecording').disabled = true; 

                    document.getElementById('stopRecording').disabled = false; 

                    mediaRecorder.addEventListener('dataavailable', event => { 

                        audioChunks.push(event.data); 

                    }); 

                    mediaRecorder.addEventListener('stop', () => { 

                        const audioBlob = new Blob(audioChunks); 

                        const audioUrl = URL.createObjectURL(audioBlob); 

                        const audio = document.getElementById('audioPlayback'); 

                        audio.src = audioUrl; 

                        uploadAudio(audioBlob); 

                    }); 

                }); 

        }); 

        document.getElementById('stopRecording').addEventListener('click', () => { 

            mediaRecorder.stop(); 

            document.getElementById('startRecording').disabled = false; 

            document.getElementById('stopRecording').disabled = true; 

        }); 

        document.getElementById('goToUploads').addEventListener('click', () => { 

            window.location.href = '/audio/list'; 

        }); 

        function uploadAudio(audioBlob) { 

            const formData = new FormData(); 

            formData.append('audio', audioBlob, 'voice-recording.webm'); 

            fetch('/api/upload-audio', { 

                method: 'POST', 

                body: formData, 

                headers: { 

                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 

                }, 

            }) 

            .then(response => response.json()) 

            .then(data => { 

                console.log('Audio uploaded successfully:', data); 

                window.location.href = '/audio/list'; 

            }) 

            .catch(error => console.error('Error uploading audio:', error)); 

        } 

    </script> 

</body> 

</html> 