<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Site;
use App\Modules\Core\Models\Tenant;
use App\Plugins\Reservations\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder dla rezerwacji z produkcji 2wheels-rental.pl.
 *
 * Dane pochodzą z backup_2wheels_20260125.sql (Strapi CMS).
 */
class ReservationSeeder extends Seeder
{
    /**
     * Tenant dla seedera.
     */
    protected ?Tenant $tenant = null;

    /**
     * Uruchomienie seedera.
     */
    public function run(): void
    {
        // Pobierz tenant (demo-studio lub pierwszy dostępny)
        $this->tenant = Tenant::where('slug', 'demo-studio')->first()
            ?? Tenant::first();

        if (!$this->tenant) {
            $this->command->error('Brak żadnego tenanta. Uruchom TenantSeeder.');
            return;
        }

        // Binduj tenant do aplikacji
        app()->instance('current_tenant', $this->tenant);

        // Pobierz site 2wheels-rental
        $site = Site::where('slug', '2wheels-rental')->first();

        if (!$site) {
            $this->command->warn('Brak strony 2wheels-rental. Tworzę ją...');

            $site = Site::create([
                'tenant_id' => $this->tenant->id,
                'name' => '2Wheels Rental',
                'slug' => '2wheels-rental',
                'status' => 'live',
                'production_url' => 'https://2wheels-rental.pl',
            ]);
        }

        $this->command->info('Seedowanie rezerwacji z produkcji Strapi...');

        // Wyczyść istniejące rezerwacje
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Reservation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Dane rezerwacji z backup_2wheels_20260125.sql
        // motorcycle_id teraz przechowuje nazwę motocykla jako string
        $reservations = [
            [
                'customer_name' => 'Test Rezerwacji',
                'customer_email' => 'test@test.pl',
                'customer_phone' => '123456789',
                'motorcycle_id' => 'Kawasaki Z650',
                'pickup_date' => '2026-01-25',
                'return_date' => '2026-01-28',
                'notes' => 'Rezerwacja: Kawasaki Kawasaki Z650',
                'status' => 'pending',
                'rodo_consent' => true,
                'created_at' => '2026-01-18 05:31:00',
            ],
            [
                'customer_name' => 'Piotr Adamczyk',
                'customer_email' => 'piotr.k.adamczyk@gmail.com',
                'customer_phone' => '791223597',
                'motorcycle_id' => 'Yamaha Tenere 700 Extreme',
                'pickup_date' => '2026-01-19',
                'return_date' => '2026-01-21',
                'notes' => 'Rezerwacja: Yamaha Yamaha Tenere 700 Extreme',
                'status' => 'pending',
                'rodo_consent' => true,
                'created_at' => '2026-01-18 05:41:57',
            ],
            [
                'customer_name' => 'Piotr Adamczyk',
                'customer_email' => 'piotr.k.adamczyk@gmail.com',
                'customer_phone' => '791223597',
                'motorcycle_id' => 'Kawasaki Z650',
                'pickup_date' => '2026-01-19',
                'return_date' => '2026-01-23',
                'notes' => 'Rezerwacja: Kawasaki Kawasaki Z650',
                'status' => 'pending',
                'rodo_consent' => true,
                'created_at' => '2026-01-18 05:50:15',
            ],
            [
                'customer_name' => 'Piotr Adamczyk',
                'customer_email' => 'piotr.k.adamczyk@gmail.com',
                'customer_phone' => '791223597',
                'motorcycle_id' => 'MRF 140 SM Supermoto',
                'pickup_date' => '2026-01-20',
                'return_date' => '2026-01-22',
                'notes' => '[MRF PitBike MRF 140 SM Supermoto] Test',
                'status' => 'pending',
                'rodo_consent' => true,
                'created_at' => '2026-01-19 10:34:32',
            ],
            [
                'customer_name' => 'Kamil',
                'customer_email' => 'kamil.wyraz@gmail.com',
                'customer_phone' => '504313622',
                'motorcycle_id' => 'YCF 125 SM',
                'pickup_date' => '2026-01-22',
                'return_date' => '2026-01-25',
                'notes' => '[YCF PitBike YCF 125 SM] rezerwuje',
                'status' => 'pending',
                'rodo_consent' => true,
                'created_at' => '2026-01-21 07:11:01',
            ],
        ];

        foreach ($reservations as $data) {
            Reservation::create([
                'tenant_id' => $this->tenant->id,
                'site_id' => $site->id,
                'motorcycle_id' => $data['motorcycle_id'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'pickup_date' => $data['pickup_date'],
                'return_date' => $data['return_date'],
                'notes' => $data['notes'],
                'status' => $data['status'],
                'rodo_consent' => $data['rodo_consent'],
                'rodo_consent_at' => $data['rodo_consent'] ? $data['created_at'] : null,
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
        }

        $this->command->info('Utworzono ' . count($reservations) . ' rezerwacji.');
    }
}
