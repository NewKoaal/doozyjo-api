<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAudioController;

Route::get('/', function() {
    return view('transcript');
});

Route::post('/', [ProcessAudioController::class, 'postToWhisper']);

