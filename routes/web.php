<?php

use App\Http\Controllers\DahuaWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard');

    Route::livewire('/cameras', 'pages::cameras.index')->name('cameras.index');

    Route::livewire('/clients', 'pages::clients.index')->name('clients.index');

    Route::livewire('/events', 'pages::events.index')->name('events.index');
});

Route::get('/webhooks/dahua', DahuaWebhookController::class);

require __DIR__ . '/settings.php';
