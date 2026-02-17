<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use App\Modules\Core\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder tworzący konto dla klienta 2Wheels Rental.
 *
 * Tworzy:
 * - Użytkownika z rolą 'client'
 * - Site '2wheels-rental'
 */
class TwoWheelsClientSeeder extends Seeder
{
    /**
     * Uruchomienie seedera.
     */
    public function run(): void
    {
        // Pobierz tenant
        $tenant = Tenant::where('slug', 'demo-studio')->first()
            ?? Tenant::first();

        if (!$tenant) {
            $this->command->error('Brak tenanta. Uruchom TenantSeeder.');
            return;
        }

        // Pobierz lub utwórz Site
        $site = Site::firstOrCreate(
            ['slug' => '2wheels-rental'],
            [
                'tenant_id' => $tenant->id,
                'name' => '2Wheels Rental',
                'status' => 'live',
                'production_url' => 'https://2wheels-rental.pl',
                'staging_url' => 'https://2wheels-dev.octadecimal.studio',
            ]
        );

        $this->command->info("Site: {$site->name} (ID: {$site->id})");

        // Utwórz użytkownika dla 2wheels
        $user = User::firstOrCreate(
            ['email' => 'admin@2wheels-rental.pl'],
            [
                'name' => '2Wheels Admin',
                'password' => Hash::make('2wheels2026!'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]
        );

        // Przypisz rolę 'client'
        if (!$user->hasRole('client')) {
            $user->assignRole('client');
        }

        $this->command->info("User: {$user->email} (rola: client)");

        $this->command->newLine();
        $this->command->info('=== DANE LOGOWANIA 2WHEELS ===');
        $this->command->info('Email: admin@2wheels-rental.pl');
        $this->command->info('Hasło: 2wheels2026!');
        $this->command->info('==============================');
    }
}
