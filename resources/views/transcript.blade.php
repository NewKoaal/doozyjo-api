<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Whisper Transcription</title>
    </head>
    <body>
        <form action="/whisper" method="POST" enctype="multipart/form-data" target="blank">
            @csrf
            <label for="audio">Upload Audio File:</label>
            <input type="file" name="audio" id="audio" required>
            <button type="submit">Transcribe</button>
        </form>
    </body>
</html>
