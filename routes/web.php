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
Route::get('/aviator/start/round', [AviatorController::class, 'StartRound']);
Route::get('/aviator/finished/round',  [AviatorController::class, 'finishRound']);
Route::get('/aviator/crush/point', [AviatorController::class,'crashPoint']);

Route::get('/aviator/check/bets',[AviatorController::class,'checkBet'])->name('aviator.check.bets');
Route::post('/aviator/place/bet', [AviatorController::class, 'placeBet'])->name('aviator.place.bet');
Route::post('/aviator/cancel/bet', [AviatorController::class, 'cancelBet'])->name('aviator.cancel.bet');
Route::get('/aviator/cashout/bet', [AviatorController::class, 'cashout'])->name('aviator.cashout');

Route::get('/aviator/tabs/data', [AviatorController::class, 'tabsData']);
