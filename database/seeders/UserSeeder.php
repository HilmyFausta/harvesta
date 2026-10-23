<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Seed the default admin, staff, and viewer accounts.
     */
    public function run(): void
    {
        $this->seedAccount('admin', UserRole::Admin);
        $this->seedAccount('staff', UserRole::Staff);
        $this->seedAccount('viewer', UserRole::Viewer);
    }

    /**
     * Create or update one account from config/seeding.php.
     */
    protected function seedAccount(string $key, UserRole $role): void
    {
        $config = config("seeding.{$key}");
        $password = $config['password'];

        if (blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('Set SEED_'.strtoupper($key).'_PASSWORD before seeding in production.');
            }

            $password = 'password';
        }

        $user = User::firstOrNew(['email' => $config['email']]);
        $user->forceFill([
            'name' => $config['name'],
            'password' => $password,
            'role' => $role,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
