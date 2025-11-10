<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/api-key', [DashboardController::class, 'updateApiKey'])->name('dashboard.update-api-key');
Route::post('/ai-generate', [AIController::class, 'generate'])->name('ai.generate');
Route::get('/ai-profiles', [AIController::class, 'getProfiles'])->name('ai.profiles');
Route::post('/ai-suggest', [AIController::class, 'suggestPrompts'])->name('ai.suggestPrompts');
Route::get('/ai-models', [AIController::class, 'getModels'])->name('ai.models');
