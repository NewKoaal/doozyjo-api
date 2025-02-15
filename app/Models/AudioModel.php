<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AudioModel extends Model
{
    public static function insertTranscript($json, $transcription, $analysis) {
        DB::connection('mysql')
            ->table('transcripts')
            ->insert([
                'message' => $transcription,
                'reminder' => $json['reminder'],
                'note' => $json['note'],
                'phone' => $json['call'],
                'summary' => $json['summary'],
                'analysis' => $analysis,
                'user_id' => 1,
                'created_at' => $json['created_at']
            ]);
    }
}
