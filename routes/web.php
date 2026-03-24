<?php

use App\Livewire\Pages\Orders\NotificationRuleBuilder;
use App\Livewire\Pages\Orders\NotificationRules;
use App\Livewire\Pages\Orders\OrderHistory;
use App\Livewire\Pages\Orders\ScheduledReports;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('orders/history', OrderHistory::class)->name('orders.history');
    Route::livewire('orders/scheduled-reports', ScheduledReports::class)->name('orders.scheduled-reports');
    Route::livewire('orders/notification-rules', NotificationRules::class)->name('orders.notification-rules');
    Route::livewire('orders/notification-rules/create', NotificationRuleBuilder::class)->name('orders.notification-rules.create');
    Route::livewire('orders/notification-rules/{rule}/edit', NotificationRuleBuilder::class)->name('orders.notification-rules.edit');
});

require __DIR__.'/settings.php';
