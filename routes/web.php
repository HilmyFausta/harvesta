<?php

use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

// Manual database setup for Wasmer (no SSH access)
// Access: /setup-db?key=YOUR_APP_KEY_HERE
// Remove this route after successful deployment!
Route::get('/setup-db', function () {
    if (! app()->environment('production')) {
        return 'This route only works in production.';
    }

    $expectedKey = substr(config('app.key'), 7, 16); // Extract 16 chars from base64:...
    if (request('key') !== $expectedKey) {
        abort(403, 'Invalid key');
    }

    $output = [];

    // Run migrations
    Artisan::call('migrate', ['--force' => true]);
    $output[] = 'Migrations: '.Artisan::output();

    // Run seeders
    Artisan::call('db:seed', ['--force' => true]);
    $output[] = 'Seeder: '.Artisan::output();

    // Cache config
    Artisan::call('config:cache');
    $output[] = 'Config cached';

    return response('<pre>'.implode("\n\n", $output).'</pre>');
})->middleware('throttle:3,60'); // Max 3 requests per hour

Route::middleware('auth')->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');

    // Placeholder pages (PRD 7.1). Each module owner replaces its line with
    // the real routes, keeping the `<module>.index` route name.
    Route::livewire('trees', 'pages::trees.index')->name('trees.index');
    Route::livewire('trees/create', 'pages::trees.create')->name('trees.create');
    Route::livewire('trees/{tree}', 'pages::trees.show')->name('trees.show');
    Route::livewire('trees/{tree}/edit', 'pages::trees.edit')->name('trees.edit');
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
