<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ProfileController; // This line is now correct

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/api-key', [DashboardController::class, 'updateApiKey'])->name('dashboard.update-api-key');
Route::delete('/dashboard/api-key/{provider}', [DashboardController::class, 'destroyApiKey'])->name('dashboard.destroy-api-key');
Route::post('/dashboard/default-handler', [DashboardController::class, 'updateDefaultHandler'])->name('dashboard.update-default-handler');

Route::get('/prompts/create', [DashboardController::class, 'createPrompt'])->name('prompts.create');
Route::post('/prompts', [DashboardController::class, 'storePrompt'])->name('prompts.store');
Route::get('/prompts/{key}/edit', [DashboardController::class, 'editPrompt'])->name('prompts.edit');
Route::put('/prompts/{key}', [DashboardController::class, 'updatePrompt'])->name('prompts.update');
Route::delete('/prompts/{key}', [DashboardController::class, 'destroyPrompt'])->name('prompts.destroy');

Route::get('profiles/create', [ProfileController::class, 'create'])->name('profiles.create');
Route::post('profiles', [ProfileController::class, 'store'])->name('profiles.store');


Route::post('/ai-generate', [AIController::class, 'generate'])->name('ai.generate');
Route::get('/ai-profiles', [AIController::class, 'getProfiles'])->name('ai.profiles');
Route::post('/ai-suggest', [AIController::class, 'suggestPrompts'])->name('ai.suggestPrompts');
Route::get('/ai-models', [AIController::class, 'getModels'])->name('ai.models');
