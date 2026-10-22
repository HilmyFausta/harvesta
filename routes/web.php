<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Placeholder pages (PRD 7.1). Each module owner replaces its line with
    // the real routes, keeping the `<module>.index` route name.
    Route::view('trees', 'coming-soon', ['title' => 'Trees'])->name('trees.index');
    Route::view('care-logs', 'coming-soon', ['title' => 'Care Logs'])->name('care-logs.index');
    Route::view('harvests', 'coming-soon', ['title' => 'Harvests'])->name('harvests.index');
    Route::view('categories', 'coming-soon', ['title' => 'Categories'])->name('categories.index');
    Route::view('tree-types', 'coming-soon', ['title' => 'Tree Types'])->name('tree-types.index');
    Route::view('blocks', 'coming-soon', ['title' => 'Blocks'])->name('blocks.index');

    Route::middleware('role:admin')->group(function () {
        Route::view('expenses', 'coming-soon', ['title' => 'Expenses'])->name('expenses.index');
        Route::view('sales', 'coming-soon', ['title' => 'Sales'])->name('sales.index');
        Route::view('reports', 'coming-soon', ['title' => 'Reports'])->name('reports.index');
        Route::view('users', 'coming-soon', ['title' => 'Users'])->name('users.index');
    });
});

require __DIR__.'/settings.php';
