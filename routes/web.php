<?php

use App\Livewire\Pages\Orders\OrderHistory;
use App\Livewire\Pages\Orders\ScheduledReports;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('orders/history', OrderHistory::class)->name('orders.history');
    Route::livewire('orders/scheduled-reports', ScheduledReports::class)->name('orders.scheduled-reports');
});

require __DIR__.'/settings.php';
