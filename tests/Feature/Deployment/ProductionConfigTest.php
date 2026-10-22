<?php

namespace Tests\Feature\Deployment;

use App\Models\Tree;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionConfigTest extends TestCase
{
    public function test_photo_urls_point_to_the_public_supabase_bucket(): void
    {
        config(['filesystems.disks.photos' => [
            'driver' => 's3',
            'root' => 'photos',
            'url' => 'https://abcd1234.supabase.co/storage/v1/object/public/harvesta-photos',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'ap-southeast-1',
            'bucket' => 'harvesta-photos',
            'endpoint' => 'https://abcd1234.storage.supabase.co/storage/v1/s3',
            'use_path_style_endpoint' => true,
        ]]);
        Storage::forgetDisk('photos');

        $tree = new Tree(['photo_path' => 'trees/t-0001.jpg']);

        $this->assertSame(
            'https://abcd1234.supabase.co/storage/v1/object/public/harvesta-photos/photos/trees/t-0001.jpg',
            $tree->photo_url,
        );
    }

    public function test_urls_use_https_behind_the_hosting_proxy(): void
    {
        $this->get('/login', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'harvesta.azurewebsites.net',
            'X-Forwarded-Port' => '443',
        ])
            ->assertOk()
            ->assertSee('https://harvesta.azurewebsites.net', false)
            ->assertDontSee('http://harvesta.azurewebsites.net', false);
    }

    public function test_seeding_in_production_requires_real_passwords(): void
    {
        $this->app['env'] = 'production';
        config(['seeding.admin.password' => null]);

        $this->expectExceptionMessage('Set SEED_ADMIN_PASSWORD before seeding in production.');

        $this->app->make(UserSeeder::class)->run();
    }
}
