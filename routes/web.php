<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\AiAssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tasks', [TodoController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TodoController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{todo}', [TodoController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{todo}', [TodoController::class, 'destroy'])->name('tasks.destroy');
Route::post('/assistant/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:10,1')->name('assistant.chat');
