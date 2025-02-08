<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAudioController;

Route::post('/whisper/binary', [ProcessAudioController::class, 'getAudio']);