<?php

use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware('auth')->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');

    // Placeholder pages (PRD 7.1). Each module owner replaces its line with
    // the real routes, keeping the `<module>.index` route name.
    Route::view('trees', 'coming-soon', ['title' => 'Trees'])->name('trees.index');
    Route::livewire('care-logs', 'pages::care-logs.index')->name('care-logs.index');
    Route::livewire('care-logs/create', 'pages::care-logs.create')->name('care-logs.create');
    Route::livewire('care-logs/{careLog}/edit', 'pages::care-logs.edit')->name('care-logs.edit');
    Route::livewire('harvests', 'pages::harvests.index')->name('harvests.index');
    Route::livewire('harvests/create', 'pages::harvests.create')->name('harvests.create');
    Route::livewire('harvests/{harvest}/edit', 'pages::harvests.edit')->name('harvests.edit');
    Route::livewire('categories', 'pages::categories.index')->name('categories.index');
    Route::livewire('categories/create', 'pages::categories.create')->name('categories.create');
    Route::livewire('categories/{category}/edit', 'pages::categories.edit')->name('categories.edit');
    Route::livewire('tree-types', 'pages::tree-types.index')->name('tree-types.index');
    Route::livewire('tree-types/create', 'pages::tree-types.create')->name('tree-types.create');
    Route::livewire('tree-types/{treeType}/edit', 'pages::tree-types.edit')->name('tree-types.edit');
    Route::livewire('blocks', 'pages::blocks.index')->name('blocks.index');
    Route::livewire('blocks/create', 'pages::blocks.create')->name('blocks.create');
    Route::livewire('blocks/{block}', 'pages::blocks.show')->name('blocks.show');
    Route::livewire('blocks/{block}/edit', 'pages::blocks.edit')->name('blocks.edit');

    Route::middleware('role:admin')->group(function () {
        Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
        Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
        Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');
        Route::livewire('sales', 'pages::sales.index')->name('sales.index');
        Route::livewire('sales/create', 'pages::sales.create')->name('sales.create');
        Route::livewire('sales/{sale}/edit', 'pages::sales.edit')->name('sales.edit');
        Route::livewire('reports', 'pages::reports.index')->name('reports.index');
        Route::get('reports/export', ReportExportController::class)->name('reports.export');
        Route::livewire('users', 'pages::users.index')->name('users.index');
        Route::livewire('users/create', 'pages::users.create')->name('users.create');
        Route::livewire('users/{user}/edit', 'pages::users.edit')->name('users.edit');
    });
});

require __DIR__.'/settings.php';
