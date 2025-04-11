<?php

namespace App\Http\Controllers;

use App\Models\AudioModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class ProcessAudioController extends Controller
{
    protected $prompt = "
        Analyze and organize the provided text to understand its meaning. Based on the analysis, categorize actions or information using these labels (insert label name in your response is mandatory):
            -Reminder: For adding a future event to a calendar. Include \"Title\" (select from Appointment, Medical Appointment, Grocery Shopping, Tech Shopping, Furniture Shopping, Clothes Shopping, Birthday, Daily Tasks, Meeting, Interview, Drink Water, Take Medication, Wellness Routine, Important Call, Creative Idea, Prepare Presentation, Project Deadline, House Chores, Write Report, Client Follow-up, Work on Music, Creative Brainstorming, Writing Session, Exam Revision, Homework/Assignment, Read a Book, Online Course, Call a Family Member, Plan Outing, Visit Someone, Buy Gift, Pay a Bill, Check Bank Balance, Track Investments, Book a Craftsman, Check Weather, Track Package), \"Date\" (if known), and \"Task\" (bulletpoints with hyphen). Add a \"Recommendation\" to give additional instructions that you would suggest (keep it very short and simple). Add an \"Icon\" to represent the task visually. Use emojis or symbols that are relevant to the task. For example:
                    Grocery Shopping → 🛒,
                    Birthday → 🎁 or 🛍,
                    Medical Appointment → 📍 or 🏥 or 🩺,
                    Take Medication → 💊 or ⏰,
                    Drink Water → 🚰,
                    Wellness Routine → 🧘 or 🏃,
                    House Chores → 📋 or 🔄,
                    Meeting → 🎥 or 📄,
                    Project Deadline → ✅ or 📂,
                    Important Call → 📞 or 📆,
                    Prepare Presentation → 📂 or 📜,
                    Write Report → 📝 or 📤,
                    Client Follow-up → 📊 or 📞,
                    Creative Idea → ✍️ or 🎙,
                    Work on Music → 🎵 or 📄,
                    Creative Brainstorming → 🧠 or 🎙,
                    Writing Session → 📝 or 🔔,
                    Exam Revision → 📖 or 📆,
                    Homework/Assignment → 📂 or 📤,
                    Read a Book → 📚 or 🎧,
                    Online Course → 🎥 or 📝,
                    Call a Family Member → 📞 or 📅,
                    Plan Outing → 📍 or 📆,
                    Visit Someone → 📍,
                    Buy Gift → 🎁 or 🛍,
                    Pay a Bill → 💳 or 📆,
                    Check Bank Balance → 🏦,
                    Track Investments → 📈,
                    Book a Craftsman → 📞 or 📅,
                    Check Weather → 🌤,
                    Track Package → 📦.
            
            -Note: For saving as an idea or reference. Provide a \"Title\" (summary of the idea) and \"Content\" (detailed information to keep long-term).
            -Call: For sending a message to someone. Include \"To\" (contact information) and \"Content\" (message details).
            -Question: For a need of an information. Include \"Question\" (the question asked) and \"Answer\" (your answer based on researches).
            -Completion: For a finished or completed task. Include \"Task\" (the task finished or completed and what it includes) and \"Date\" (when the task were completed).
            -Cancellation: For a canceled event. Include \"Title\" (the canceled event) and \"Date\" (when were the event supposed to happen) and \"Reason\" (why is it cancelled).
            -Delay: For a delayed event (earlier or later). Include \"Title\" (the delayed event) and \"Date\" (when is the event delayed to) and \"Reason\" (why is it delayed).
            -Summary: Finish with a Summary explaining how the content was organized. Use plain text format.

            IF THE MESSAGE RETURN A REMINDER THEN DISABLE NOTE AND CALL AND QUESTION AND CANCELLATION AND DELAY
            IF THE MESSAGE RETURN A NOTE THEN DISABLE REMINDER AND CALL AND QUESTION AND CANCELLATION AND DELAY
            IF THE MESSAGE RETURN A CALL THEN DISABLE NOTE AND QUESTION
            IF THE MESSAGE RETURN A QUESTION THEN DISABLE NOTE AND CALL AND REMINDER AND CANCELLATION AND DELAY
            IF THE MESSAGE RETURN A COMPLETION THEN DISABLE NOTE AND CALL AND REMINDER AND QUESTION AND CANCELLATION AND DELAY
            IF THE MESSAGE RETURN A CANCELLATION THEN DISABLE NOTE AND CALL AND REMINDER AND QUESTION AND DELAY
            IF THE MESSAGE RETURN A DELAY THEN DISABLE NOTE AND CALL AND REMINDER AND QUESTION AND CANCELLATION
            IF THE MESSAGE DO NOT CONTAIN A DATE THEN SET THE DATE TO TODAY
            IF THE MESSAGE DO NOT CONTAIN A REASON FOR CANCELLATION OR DELAY THEN INCLUDE A NONE REASON
            ALWAYS INCLUDE THE SUMMARY

            FOR REMINDER RESPONSE USE THIS FORMAT:
            reminder:
            -title:
            -date:
            -task:
            -recommendation:
            -icon:

            FOR NOTE RESPONSE USE THIS FORMAT:
            note:
            -title:
            -content:

            FOR CALL RESPONSE USE THIS FORMAT:
            call:
            -to:
            -content:

            FOR QUESTION RESPONSE USE THIS FORMAT:
            question:
            -question:
            -answer:

            FOR COMPLETION RESPONSE USE THIS FORMAT:
            completion:
            -task:
            -date:

            FOR CANCELLATION RESPONSE USE THIS FORMAT:
            cancellation:
            -title:
            -date:
            -reason:

            FOR DELAY RESPONSE USE THIS FORMAT:
            delay:
            -title:
            -date:
            -reason:

            FOR SUMMARY RESPONSE USE THIS FORMAT:
            summary:
            -text:

            DO NOT USE THIS * NEITHER **

            INSERT END TAG
    ";

    // PARSER
    public function transcriptParser($analysis) {
        // Split raw analysis by the "END TAG"
        $parts = explode("END TAG", $analysis);
        $parsedData = trim($parts[0]);

        $jsonResult = [];

        // Extract Reminder
        if (preg_match('/reminder:\s*-title:\s*(.*?)\s*-date:\s*(.*?)\s*-task:\s*(.*?)\s*-recommendation:\s*(.*?)\s*-icon:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['reminder'] = [
                'title' => trim($matches[1]),
                'date' => trim($matches[2]),
                'task' => trim($matches[3]),
                'recommendation' => trim($matches[4]),
                'icon' => trim($matches[5]),
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

        // Extract Question
        if (preg_match('/question:\s*-question:\s*(.*?)\s*-answer:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['question'] = [
                'question' => trim($matches[1]),
                'answer' => trim($matches[2]),
            ];
        }

        // Extract Completion
        if (preg_match('/completion:\s*-task:\s*(.*?)\s*-date:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['completion'] = [
                'task' => trim($matches[1]),
                'date' => trim($matches[2]),
            ];
        }

        // Extract Cancellation
        if (preg_match('/cancellation:\s*-title:\s*(.*?)\s*-date:\s*(.*?)\s*-reason:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['cancellation'] = [
                'title' => trim($matches[1]),
                'date' => trim($matches[2]),
                'reason' => trim($matches[3]),
            ];
        }

        // Extract Delay
        if (preg_match('/delay:\s*-title:\s*(.*?)\s*-date:\s*(.*?)\s*-reason:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['delay'] = [
                'title' => trim($matches[1]),
                'date' => trim($matches[2]),
                'reason' => trim($matches[3]),
            ];
        }

        // Extract Summary
        if (preg_match('/summary:\s*-text:\s*(.*?)(\n|$)/s', $parsedData, $matches)) {
            $jsonResult['summary'] = [
                'text' => trim($matches[1]),
            ];
        }
        $jsonResult['created_at'] = Carbon::now()->toDateTimeString();
        //$jsonResult['parsed_data'] = $parsedData;

        return $jsonResult;
    }

    // API
    public function getAudio(Request $request, $audio_binary = null) {
        // USER HANDLING
        //

        $binaryData = $request->getContent();
        if (empty($binaryData)) {
            return response()->json(['success' => false, 'message' => 'No binary data received.'], 400);
        }

        $filename = time() . '.mp3';
        $path = 'audio/' . $filename;
        Storage::disk('public')->put($path, $binaryData);

        $filePath = storage_path('app/public/' . $path);

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'File could not be stored.'], 500);
        }

        try {
            // Send the file to Whisper for transcription
            $response = OpenAI::audio()->transcribe([
                'file' => fopen($filePath, 'r'),
                'model' => 'whisper-1',
            ]);
            $transcription = $response['text'];

            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant that organizes and categorizes information.'],
                ['role' => 'user', 'content' => $this->prompt . "

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

        $jsonResult = $this->transcriptParser($analysis);

        AudioModel::insertTranscript($jsonResult, $transcription, $analysis);

        // Return JSON result
        return response()->json([
            'success' => true,
            'transcription' => $jsonResult
        ], 200);
    }

    // WEB
    public function postToWhisper(Request $request) {
        try {
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
                ['role' => 'user', 'content' => $this->prompt . "

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

        $jsonResult = $this->transcriptParser($analysis);

        //dd($jsonResult);

        AudioModel::insertTranscript($jsonResult, $transcription, $analysis);

        // Return JSON result
        return response()->json(['success' => true, 'data' => $jsonResult]);
    }
}
