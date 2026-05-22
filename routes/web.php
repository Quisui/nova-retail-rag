<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');

Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/ask', [ChatController::class, 'ask'])->name('chat.ask');
Route::get('/history', [ChatController::class, 'history'])->name('chat.history');
