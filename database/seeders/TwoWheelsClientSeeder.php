<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
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
 * - Powiązanie z Customer '2Wheels Rental'
 * - Powiązanie z Site '2wheels-rental'
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

        // Binduj tenant
        app()->instance('current_tenant', $tenant);

        // Pobierz lub utwórz Customer
        $customer = Customer::firstOrCreate(
            ['slug' => '2wheels-rental'],
            [
                'tenant_id' => $tenant->id,
                'name' => '2Wheels Rental',
                'email' => 'kontakt@2wheels-rental.pl',
                'phone' => '+48 123 456 789',
                'status' => 'active',
                'source' => 'direct',
                'notes' => 'Pierwszy klient CMS - wypożyczalnia motocykli',
            ]
        );

        $this->command->info("Customer: {$customer->name} (ID: {$customer->id})");

        // Pobierz lub utwórz Site
        $site = Site::firstOrCreate(
            ['slug' => '2wheels-rental'],
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
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

        // Powiąż użytkownika z Customer
        if (!$user->customers()->where('customers.id', $customer->id)->exists()) {
            $user->customers()->attach($customer->id, [
                'role' => 'admin',
                'can_view_billing' => true,
                'can_manage_users' => false,
                'notify_new_invoice' => true,
                'notify_site_updates' => true,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);
            $this->command->info("-> Połączono z Customer");
        }

        // Powiąż użytkownika z Site
        if (!$user->sites()->where('sites.id', $site->id)->exists()) {
            $user->sites()->attach($site->id, [
                'role' => 'admin',
                'can_publish' => false, // Klient nie może publikować
                'can_manage_media' => true,
                'can_view_analytics' => true,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);
            $this->command->info("-> Połączono z Site");
        }

        $this->command->newLine();
        $this->command->info('=== DANE LOGOWANIA 2WHEELS ===');
        $this->command->info('Email: admin@2wheels-rental.pl');
        $this->command->info('Hasło: 2wheels2026!');
        $this->command->info('==============================');
    }
}
