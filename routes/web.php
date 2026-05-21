<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AviatorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test2');
});


Route::get('/avaitor/launch/url', [AviatorController::class, 'launchUrl'])->name('aviator.launch.url');
Route::get('/launch/aviator', [AviatorController::class, 'launch'])->name('aviator.launch');

// Aviator Round
Route::get('/aviator/genetate/round', [AviatorController::class, 'generateRound']);
Route::get('/aviator/finished/round',  [AviatorController::class, 'finishRound']);
Route::get('/aviator/crush/point', [AviatorController::class,'crashPoint']);
