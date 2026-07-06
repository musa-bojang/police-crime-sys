<?php
use App\Http\Controllers\EvidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/evidence/{image}', [EvidenceController::class, 'show'])
    ->middleware(['auth', 'signed'])
    ->name('evidence.show');