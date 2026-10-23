<?php

/**
 * Wasmer initialization script.
 * Runs before Laravel boots to prepare the environment.
 */
$basePath = dirname(__DIR__);

// Ensure storage directories exist and are writable
$storageDirs = [
    $basePath.'/storage/app',
    $basePath.'/storage/framework/cache/data',
    $basePath.'/storage/framework/sessions',
    $basePath.'/storage/framework/views',
    $basePath.'/storage/logs',
];

foreach ($storageDirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @chmod($dir, 0755);
}

// Run migrations on first boot (check if migrations table exists)
if (getenv('APP_ENV') === 'production' && file_exists($basePath.'/artisan')) {
    // Only run if we can connect to DB
    try {
        $dbHost = getenv('DB_HOST');
        $dbPort = getenv('DB_PORT') ?: '5432';
        $dbName = getenv('DB_DATABASE');
        $dbUser = getenv('DB_USERNAME');
        $dbPass = getenv('DB_PASSWORD');

        if ($dbHost && $dbUser && $dbPass) {
            $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode=require";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // Check if migrations table exists
            $stmt = $pdo->query("SELECT to_regclass('public.migrations') IS NOT NULL as exists");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $result['exists']) {
                // First boot - run migrations and seeder
                error_log('[Wasmer Init] Running first-time database setup...');

                chdir($basePath);
                exec('php artisan migrate --force --no-interaction 2>&1', $output, $code);
                error_log('[Wasmer Init] Migration output: '.implode("\n", $output));

                if ($code === 0) {
                    exec('php artisan db:seed --force --no-interaction 2>&1', $output, $code);
                    error_log('[Wasmer Init] Seeder output: '.implode("\n", $output));
                }
            } else {
                error_log('[Wasmer Init] Database already initialized, skipping migrations.');
            }
        }
    } catch (Exception $e) {
        error_log('[Wasmer Init] Database check failed: '.$e->getMessage());
    }
}
