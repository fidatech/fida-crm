<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
Route::middleware(['api.key','throttle:120,1'])->prefix('v1')->group(function(){
 Route::get('/{entity}',[ApiController::class,'index']);Route::get('/{entity}/{id}',[ApiController::class,'show']);Route::post('/{entity}',[ApiController::class,'store']);Route::put('/{entity}/{id}',[ApiController::class,'update']);
});
