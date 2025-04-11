<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AudioModel extends Model
{
    public static function insertTranscript($json, $transcription, $analysis) {
        $reminder_title = $reminder_date = $reminder_task = $reminder_recommendation = $note_title = $note_content = $phone_to = $phone_content = $summary = null;
        if (isset($json['reminder'])) {
            $reminder_title = $json['reminder']['title'];
            $reminder_date = $json['reminder']['date'];
            $reminder_task = $json['reminder']['task'];
            $reminder_recommendation = $json['reminder']['recommendation'];
        }
        if (isset($json['note'])) {
            $note_title = $json['note']['title'];
            $note_content = $json['note']['content'];
        }
        if (isset($json['call'])) {
            $phone_to = $json['call']['to'];
            $phone_content = $json['call']['content'];
        }
        if (isset($json['advice'])) {
            $advice_question = $json['call']['question'];
            $advice_answer = $json['call']['answer'];
        }
        if (isset($json['summary'])) {
            $summary = $json['summary']['text'];
        }


        DB::connection('mysql')
            ->table('transcript')
            ->insert([
                'message' => $transcription ?? null,
                'reminder_title' => $reminder_title ?? null,
                'reminder_date' => $reminder_date ?? null,
                'reminder_task' => $reminder_task ?? null,
                'reminder_recommendation' => $reminder_recommendation ?? null,
                'note_title' => $note_title ?? null,
                'note_content' => $note_content ?? null,
                'phone_to' => $phone_to ?? null,
                'phone_content' => $phone_content ?? null,
                'summary' => $summary ?? null,
                'analysis' => $analysis ?? null,
                'user_id' => 1 ?? null,
                'created_at' => $json['created_at']
            ]);
    }
}
