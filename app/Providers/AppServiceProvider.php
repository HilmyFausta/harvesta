<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Define role-based gates. Model abilities live in app/Policies.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Keep in sync with the checklist in <x-password-requirements>.
        Password::defaults(fn (): Password => Password::min(8)
            ->numbers()
            ->rules([function (string $attribute, mixed $value, Closure $fail): void {
                if (! preg_match('/\p{Lu}/u', (string) $value)) {
                    $fail(__('The :attribute field must contain at least one uppercase letter.'));
                }
            }]),
        );
    }
}
