<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/cameras', 'pages::cameras.index')->name('cameras.index');

    Route::livewire('/clients', 'pages::clients.index')->name('clients.index');
});

require __DIR__.'/settings.php';
