<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAudioController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/whisper', function() {
    return view('transcript');
});

Route::post('/whisper', [ProcessAudioController::class, 'postToWhisper']);

