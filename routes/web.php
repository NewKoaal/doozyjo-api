<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAudioController;

Route::get('/', function() {
    return view('transcript');
});

Route::get('/test', function() {
    return view('audinote');
});

Route::post('/', [ProcessAudioController::class, 'postToWhisper'])->name('upload.audio');

