<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Whisper Transcription</title>
    </head>
    <body>
        <form action="/" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="audio">Upload Audio File:</label>
            <input type="file" name="audio" id="audio" required>
            <button type="submit">Transcribe</button>
        </form>
    </body>
    @if (isset($jsonResult))
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
</html>
