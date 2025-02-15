<?php

namespace App\Http\Controllers;

use App\Models\AudioModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class ProcessAudioController extends Controller
{
    public function getAudio(Request $request, $audio_binary = null) {
        // Check if the request contains a valid binary stream
        // Get binary data from the request
        $binaryData = $request->getContent();
        if (empty($binaryData)) {
            return response()->json(['success' => false, 'message' => 'No binary data received.'], 400);
        }

        // Define a unique filename for the audio file
        $filename = time() . '.mp3';

        // Store the binary data in the 'public' disk
        $path = 'audio/' . $filename;
        Storage::disk('public')->put($path, $binaryData);

        // Get the absolute file path
        $filePath = storage_path('app/public/' . $path);

        // Ensure the file exists
        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'File could not be stored.'], 500);
        }

        try {
            // Send the file to Whisper for transcription
            $response = OpenAI::audio()->transcribe([
                'file' => fopen($filePath, 'r'), // Open the file as a resource
                'model' => 'whisper-1',
            ]);
            $transcription = $response['text'];

            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant that organizes and categorizes information.'],
                ['role' => 'user', 'content' => "
                    Analyze and organize the provided text to understand its meaning. Based on the analysis, categorize actions or information using these labels (insert label name in your response is mandatory):
                    -Reminder: For adding to a calendar. Include \"Title\" (select from Appointment, Medical Appointment, Grocery Shopping, Tech Shopping, Furniture Shopping, Clothes Shopping, Birthday, Daily Tasks, Meeting, Interview), \"Date\" (if known), and \"Task\" (a brief sentence or to-do list). Add a \"Recommendation\" to give additional instructions that you would suggest (keep it very short and simple).
                    -Note: For saving as an idea or reference. Provide a \"Title\" (summary of the idea) and \"Content\" (detailed information to keep long-term).
                    -Call: For sending a message to someone. Include \"To\" (contact information) and \"Content\" (message details).
                    -Summary: Finish with a Summary explaining how the content was organized. Use plain text format.

                    IF THE MESSAGE RETURN A REMINDER THEN DISABLE NOTE AND CALL
                    IF THE MESSAGE RETURN A NOTE THEN DISABLE REMINDER AND CALL
                    IF THE MESSAGE RETURN A CALL THEN DISABLE NOTE

                    FOR REMINDER RESPONSE USE THIS FORMAT:
                    reminder:
                    -title:
                    -date:
                    -task:
                    -recommendation:

                    FOR NOTE RESPONSE USE THIS FORMAT:
                    note:
                    -title:
                    -content:

                    FOR CALL RESPONSE USE THIS FORMAT:
                    call:
                    -to:
                    -content:

                    FOR SUMMARY RESPONSE USE THIS FORMAT:
                    summary:
                    -text:

                    DO NOT USE THIS * NEITHER **

                    INSERT END TAG

                    Text to analyze:
                    \"$transcription\"
                "]
            ];

            $analysisResponse = OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 500,
            ]);

            $analysis = $analysisResponse['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $parts = explode("END TAG", $analysis);
        $parsedData = trim($parts[0]);

        $jsonResult = [];

        // Extract Reminder
        if (preg_match('/reminder:\s*-title:\s*(.*?)\s*-date:\s*(.*?)\s*-task:\s*(.*?)\s*-recommendation:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['reminder'] = [
                'title' => trim($matches[1]),
                'date' => trim($matches[2]),
                'task' => trim($matches[3]),
                'recommendation' => trim($matches[4]),
            ];
        }

        // Extract Note
        if (preg_match('/note:\s*-title:\s*(.*?)\s*-content:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['note'] = [
                'title' => trim($matches[1]),
                'content' => trim($matches[2]),
            ];
        }

        // Extract Call
        if (preg_match('/call:\s*-to:\s*(.*?)\s*-content:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['call'] = [
                'to' => trim($matches[1]),
                'content' => trim($matches[2]),
            ];
        }

        // Extract Summary
        if (preg_match('/summary:\s*-text:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['summary'] = [
                'text' => trim($matches[1]),
            ];
        }
        $jsonResult['created_at'] = Carbon::now()->toDateTimeString();

        AudioModel::insertTranscript($jsonResult, $transcription, $analysis);

        // Return JSON result
        return response()->json([
            'success' => true,
            'transcription' => $jsonResult
        ], 200);
    }

    public function postToWhisper(Request $request) {
        try {
            //dd($request);
            // Validate file input
            /*$request->validate([
                'audio' => 'required|file|mimes:wav,mp3,m4a,audio/wav|mimetypes:audio/wav,audio/mp3,audio/m4a,wav|max:10240', // Max 10MB file size
            ], [
                'audio.required' => 'Please upload an audio file.',
                'audio.mimes' => 'Only MP3, WAV, or M4A files are allowed.',
                'audio.max' => 'The file size must not exceed 10MB.',
            ]);*/

            $audioPath = $request->file('audio')->store('audio', 'public');
            $audioFile = storage_path("app/public/{$audioPath}");
        } catch (\Exception $e) {
            dd($e);
            return back()->with('error', 'An error occurred while processing your request. Please try again.');
        }

        try {
            $response = OpenAI::audio()->transcribe([
                'file' => fopen($audioFile, 'r'),
                'model' => 'whisper-1',
            ]);
            $transcription = $response['text'];

            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant that organizes and categorizes information.'],
                ['role' => 'user', 'content' => "
                    Analyze and organize the provided text to understand its meaning. Based on the analysis, categorize actions or information using these labels (insert label name in your response is mandatory):
                    -Reminder: For adding to a calendar. Include \"Title\" (select from Appointment, Medical Appointment, Grocery Shopping, Tech Shopping, Furniture Shopping, Clothes Shopping, Birthday, Daily Tasks, Meeting, Interview), \"Date\" (if known), and \"Task\" (a brief sentence or to-do list). Add a \"Recommendation\" to give additional instructions that you would suggest (keep it very short and simple).
                    -Note: For saving as an idea or reference. Provide a \"Title\" (summary of the idea) and \"Content\" (detailed information to keep long-term).
                    -Call: For sending a message to someone. Include \"To\" (contact information) and \"Content\" (message details).
                    -Summary: Finish with a Summary explaining how the content was organized. Use plain text format.

                    IF THE MESSAGE RETURN A REMINDER THEN DISABLE NOTE AND CALL
                    IF THE MESSAGE RETURN A NOTE THEN DISABLE REMINDER AND CALL
                    IF THE MESSAGE RETURN A CALL THEN DISABLE NOTE

                    FOR REMINDER RESPONSE USE THIS FORMAT:
                    reminder:
                    -title:
                    -date:
                    -task:
                    -recommendation:

                    FOR NOTE RESPONSE USE THIS FORMAT:
                    note:
                    -title:
                    -content:

                    FOR CALL RESPONSE USE THIS FORMAT:
                    call:
                    -to:
                    -content:

                    FOR SUMMARY RESPONSE USE THIS FORMAT:
                    summary:
                    -text:

                    DO NOT USE THIS * NEITHER **

                    INSERT END TAG

                    Text to analyze:
                    \"$transcription\"
                "]
            ];

            $analysisResponse = OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 500,
            ]);

            $analysis = $analysisResponse['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // Split raw analysis by the "END TAG"
        $parts = explode("END TAG", $analysis);
        $parsedData = trim($parts[0]);

        $jsonResult = [];

        // Extract Reminder
        if (preg_match('/reminder:\s*-title:\s*(.*?)\s*-date:\s*(.*?)\s*-task:\s*(.*?)\s*-recommendation:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['reminder'] = [
                'title' => trim($matches[1]),
                'date' => trim($matches[2]),
                'task' => trim($matches[3]),
                'recommendation' => trim($matches[4]),
            ];
        }

        // Extract Note
        if (preg_match('/note:\s*-title:\s*(.*?)\s*-content:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['note'] = [
                'title' => trim($matches[1]),
                'content' => trim($matches[2]),
            ];
        }

        // Extract Call
        if (preg_match('/call:\s*-to:\s*(.*?)\s*-content:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['call'] = [
                'to' => trim($matches[1]),
                'content' => trim($matches[2]),
            ];
        }

        // Extract Summary
        if (preg_match('/summary:\s*-text:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['summary'] = [
                'text' => trim($matches[1]),
            ];
        }
        $jsonResult['created_at'] = Carbon::now()->toDateTimeString();

        AudioModel::insertTranscript($jsonResult, $transcription, $analysis);

        // Return JSON result
        return response()->json(['success' => true, 'data' => $jsonResult]);
    }
}
