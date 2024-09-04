<?php

use App\Http\Controllers\MessageGroupController;
use App\Http\Controllers\MessagesController;
use Illuminate\Support\Facades\Route;

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

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::prefix('conversation')->middleware('auth')->group(function () {
    Route::get('/{id}', [MessagesController::class, 'conversation'])->name('messages.conversation');
    Route::post('/send', [MessagesController::class, 'sendMessage'])->name('messages.send');
    Route::post('/send-group', [MessagesController::class, 'sendGroupMessage'])->name('messages.send-group');
});

Route::resource('message-groups', MessageGroupController::class);
