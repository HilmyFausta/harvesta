<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Placeholder pages (PRD 7.1). Each module owner replaces its line with
    // the real routes, keeping the `<module>.index` route name.
    Route::view('trees', 'coming-soon', ['title' => 'Trees'])->name('trees.index');
    Route::livewire('care-logs', 'pages::care-logs.index')->name('care-logs.index');
    Route::livewire('care-logs/create', 'pages::care-logs.create')->name('care-logs.create');
    Route::livewire('care-logs/{careLog}/edit', 'pages::care-logs.edit')->name('care-logs.edit');
    Route::livewire('harvests', 'pages::harvests.index')->name('harvests.index');
    Route::livewire('harvests/create', 'pages::harvests.create')->name('harvests.create');
    Route::livewire('harvests/{harvest}/edit', 'pages::harvests.edit')->name('harvests.edit');
    Route::view('categories', 'coming-soon', ['title' => 'Categories'])->name('categories.index');
    Route::view('tree-types', 'coming-soon', ['title' => 'Tree Types'])->name('tree-types.index');
    Route::view('blocks', 'coming-soon', ['title' => 'Blocks'])->name('blocks.index');

    Route::middleware('role:admin')->group(function () {
        Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
        Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
        Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');
        Route::view('sales', 'coming-soon', ['title' => 'Sales'])->name('sales.index');
        Route::view('reports', 'coming-soon', ['title' => 'Reports'])->name('reports.index');
        Route::livewire('users', 'pages::users.index')->name('users.index');
        Route::livewire('users/create', 'pages::users.create')->name('users.create');
        Route::livewire('users/{user}/edit', 'pages::users.edit')->name('users.edit');
    });
});

require __DIR__.'/settings.php';
