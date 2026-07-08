<?php
use App\Http\Controllers\EvidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::get('/evidence/{image}', [EvidenceController::class, 'show'])
    ->middleware(['auth', 'signed'])
    ->name('evidence.show');