<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Content\Models\TwoWheels\Feature;
use App\Modules\Content\Models\TwoWheels\Motorcycle;
use App\Modules\Content\Models\TwoWheels\MotorcycleBrand;
use App\Modules\Content\Models\TwoWheels\MotorcycleCategory;
use App\Modules\Content\Models\TwoWheels\ProcessStep;
use App\Modules\Content\Models\TwoWheels\SiteSetting;
use App\Modules\Content\Models\TwoWheels\Testimonial;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Komenda do generowania przykładowego kontentu dla 2Wheels Rental w naszym CMS.
 */
class GenerateTwoWheelsContent extends Command
{
    /**
     * Sygnatura komendy.
     *
     * @var string
     */
    protected $signature = '2wheels:generate-content 
                            {--tenant= : UUID tenanta (opcjonalnie - użyje pierwszego aktywnego)}
                            {--schema= : Ścieżka do schema.json (opcjonalnie)}';

    /**
     * Opis komendy.
     *
     * @var string
     */
    protected $description = 'Generuje przykładowy kontent dla 2Wheels Rental w naszym CMS na podstawie schema.json';

    private ?Tenant $tenant = null;

    /**
     * Wykonaj komendę.
     */
    public function handle(): int
    {
        $this->info('🚀 Generowanie przykładowego kontentu dla 2Wheels Rental...');

        // 1. Pobierz tenanta
        $this->tenant = $this->getTenant();
        if (! $this->tenant) {
            $this->error('❌ Nie znaleziono aktywnego tenanta. Użyj --tenant=UUID lub utwórz nowego tenanta.');

            return Command::FAILURE;
        }

        $this->info("✅ Używam tenanta: {$this->tenant->name} ({$this->tenant->id})");

        // 2. Ustaw tenant w kontekście (dla BelongsToTenant)
        app()->instance('current_tenant', $this->tenant);

        // 3. Generuj przykładowy kontent
        $this->generateContent();

        $this->newLine();
        $this->info('✅ Przykładowy kontent został wygenerowany!');
        $this->info('📊 Sprawdź w Filament: /admin');

        return Command::SUCCESS;
    }

    /**
     * Generuje przykładowy kontent.
     */
    private function generateContent(): void
    {
        // 1. Site Setting (single type)
        $this->info('📄 Generowanie Site Setting...');
        SiteSetting::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'site_title' => '2Wheels Rental - Wypożyczalnia Motocykli',
                'site_description' => 'Profesjonalna wypożyczalnia motocykli. Najlepsze modele, konkurencyjne ceny, pełne ubezpieczenie.',
                'contact_phone' => '+48 123 456 789',
                'contact_email' => 'kontakt@2wheels.pl',
                'address' => 'ul. Przykładowa 123, 00-000 Warszawa',
                'opening_hours' => 'Pon-Pt: 9:00-18:00, Sob: 10:00-16:00',
                'map_coordinates' => '52.2297,21.0122',
            ]
        );
        $this->info('    ✅ Site Setting utworzony');

        // 2. Kategorie
        $this->info('📦 Generowanie kategorii...');
        $categories = [
            ['name' => 'Sport', 'slug' => 'sport', 'color' => '#dc2626', 'description' => 'Motocykle sportowe'],
            ['name' => 'Cruiser', 'slug' => 'cruiser', 'color' => '#2563eb', 'description' => 'Motocykle cruiser'],
            ['name' => 'Touring', 'slug' => 'touring', 'color' => '#16a34a', 'description' => 'Motocykle turystyczne'],
            ['name' => 'Naked', 'slug' => 'naked', 'color' => '#ca8a04', 'description' => 'Motocykle naked'],
        ];

        $createdCategories = [];
        foreach ($categories as $catData) {
            $category = MotorcycleCategory::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'slug' => $catData['slug'],
                ],
                array_merge($catData, [
                    'tenant_id' => $this->tenant->id,
                    'published' => true,
                    'published_at' => now(),
                ])
            );
            $createdCategories[] = $category;
            $this->info("    ✅ Kategoria: {$category->name}");
        }

        // 3. Marki
        $this->info('📦 Generowanie marek...');
        $brands = [
            ['name' => 'Yamaha', 'slug' => 'yamaha', 'description' => 'Japońska marka motocykli'],
            ['name' => 'Kawasaki', 'slug' => 'kawasaki', 'description' => 'Japońska marka motocykli'],
            ['name' => 'Honda', 'slug' => 'honda', 'description' => 'Japońska marka motocykli'],
            ['name' => 'Ducati', 'slug' => 'ducati', 'description' => 'Włoska marka motocykli'],
            ['name' => 'BMW', 'slug' => 'bmw', 'description' => 'Niemiecka marka motocykli'],
        ];

        $createdBrands = [];
        foreach ($brands as $brandData) {
            $brand = MotorcycleBrand::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'slug' => $brandData['slug'],
                ],
                array_merge($brandData, [
                    'tenant_id' => $this->tenant->id,
                    'published' => true,
                    'published_at' => now(),
                ])
            );
            $createdBrands[] = $brand;
            $this->info("    ✅ Marka: {$brand->name}");
        }

        // 4. Motocykle
        $this->info('📦 Generowanie motocykli...');
        $motorcycles = [
            ['name' => 'Yamaha Tracer 900 GT', 'brand' => 'yamaha', 'category' => 'touring', 'engine_capacity' => 900, 'year' => 2023, 'price_per_day' => 300.00, 'price_per_week' => 1800.00, 'price_per_month' => 6500.00, 'deposit' => 2500.00],
            ['name' => 'Kawasaki Z650', 'brand' => 'kawasaki', 'category' => 'naked', 'engine_capacity' => 650, 'year' => 2022, 'price_per_day' => 200.00, 'price_per_week' => 1200.00, 'price_per_month' => 4500.00, 'deposit' => 1500.00],
            ['name' => 'Honda CB650R', 'brand' => 'honda', 'category' => 'naked', 'engine_capacity' => 650, 'year' => 2023, 'price_per_day' => 250.00, 'price_per_week' => 1500.00, 'price_per_month' => 5500.00, 'deposit' => 2000.00],
            ['name' => 'Ducati Monster 821', 'brand' => 'ducati', 'category' => 'naked', 'engine_capacity' => 821, 'year' => 2022, 'price_per_day' => 350.00, 'price_per_week' => 2100.00, 'price_per_month' => 7500.00, 'deposit' => 3000.00],
            ['name' => 'Yamaha Tenere 700 Extreme', 'brand' => 'yamaha', 'category' => 'touring', 'engine_capacity' => 700, 'year' => 2023, 'price_per_day' => 280.00, 'price_per_week' => 1700.00, 'price_per_month' => 6000.00, 'deposit' => 2200.00],
        ];

        foreach ($motorcycles as $i => $motoData) {
            $brand = collect($createdBrands)->firstWhere('slug', $motoData['brand']);
            $category = collect($createdCategories)->firstWhere('slug', $motoData['category']);

            if (! $brand || ! $category) {
                $this->warn("    ⚠️  Pominięto {$motoData['name']} (brak brand/category)");
                continue;
            }

            // Znajdź placeholder image lub utwórz pusty wpis bez main_image_id
            $mainImageId = null;
            try {
                $placeholderImage = \App\Modules\Content\Models\Media::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $this->tenant->id)
                    ->where('is_active', true)
                    ->first();
                $mainImageId = $placeholderImage?->id;
            } catch (\Exception $e) {
                // Ignoruj błąd - main_image_id będzie null
            }

            $motorcycleData = [
                'tenant_id' => $this->tenant->id,
                'name' => $motoData['name'],
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'engine_capacity' => $motoData['engine_capacity'],
                'year' => $motoData['year'],
                'price_per_day' => $motoData['price_per_day'],
                'price_per_week' => $motoData['price_per_week'],
                'price_per_month' => $motoData['price_per_month'],
                'deposit' => $motoData['deposit'],
                'description' => "Profesjonalny motocykl {$motoData['name']} idealny do wypożyczenia. Kompletne wyposażenie, pełne ubezpieczenie.",
                'specifications' => [
                    'engine' => "{$motoData['engine_capacity']}cc",
                    'power' => '95 KM',
                    'torque' => '93 Nm',
                    'weight' => '215 kg',
                ],
                'available' => true,
                'featured' => $i < 3, // Pierwsze 3 wyróżnione
                'published' => true,
                'published_at' => now(),
            ];

            // Dodaj main_image_id tylko jeśli istnieje
            if ($mainImageId) {
                $motorcycleData['main_image_id'] = $mainImageId;
            }

            $motorcycle = Motorcycle::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'slug' => Str::slug($motoData['name']),
                ],
                $motorcycleData
            );
            $this->info("    ✅ Motocykl: {$motorcycle->name}");
        }

        // 5. Features
        $this->info('📦 Generowanie features...');
        $features = [
            ['title' => 'Szeroki wybór', 'description' => 'Ponad 50 modeli motocykli do wyboru', 'order' => 0],
            ['title' => 'Pełne ubezpieczenie', 'description' => 'Wszystkie motocykle objęte pełnym ubezpieczeniem', 'order' => 1],
            ['title' => 'Profesjonalna obsługa', 'description' => 'Doświadczony zespół gotowy pomóc', 'order' => 2],
            ['title' => 'Konkurencyjne ceny', 'description' => 'Najlepsze ceny na rynku', 'order' => 3],
            ['title' => 'Szybka rezerwacja', 'description' => 'Rezerwacja online w kilka minut', 'order' => 4],
            ['title' => 'Wsparcie 24/7', 'description' => 'Wsparcie techniczne przez całą dobę', 'order' => 5],
        ];

        foreach ($features as $featureData) {
            Feature::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'title' => $featureData['title'],
                ],
                array_merge($featureData, [
                    'tenant_id' => $this->tenant->id,
                    'published' => true,
                    'published_at' => now(),
                ])
            );
        }
        $this->info('    ✅ Features utworzone');

        // 6. Process Steps
        $this->info('📦 Generowanie process steps...');
        $steps = [
            ['step_number' => 1, 'title' => 'Wybierz motocykl', 'description' => 'Przeglądaj dostępne modele i wybierz idealny dla siebie', 'icon_name' => 'bike'],
            ['step_number' => 2, 'title' => 'Zarezerwuj termin', 'description' => 'Wybierz datę rozpoczęcia i zakończenia wypożyczenia', 'icon_name' => 'calendar'],
            ['step_number' => 3, 'title' => 'Wypełnij formularz', 'description' => 'Podaj dane kontaktowe i informacje o prawie jazdy', 'icon_name' => 'form'],
            ['step_number' => 4, 'title' => 'Odbierz motocykl', 'description' => 'Przyjedź do nas i odbierz swój motocykl', 'icon_name' => 'pickup'],
            ['step_number' => 5, 'title' => 'Ciesz się jazdą', 'description' => 'Ciesz się wolną jazdą na wybranym motocyklu', 'icon_name' => 'ride'],
        ];

        foreach ($steps as $stepData) {
            ProcessStep::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'step_number' => $stepData['step_number'],
                ],
                array_merge($stepData, [
                    'tenant_id' => $this->tenant->id,
                    'published' => true,
                    'published_at' => now(),
                ])
            );
        }
        $this->info('    ✅ Process Steps utworzone');

        // 7. Testimonials
        $this->info('📦 Generowanie testimonials...');
        $testimonials = [
            ['author_name' => 'Jan Kowalski', 'content' => 'Świetna wypożyczalnia! Motocykl w idealnym stanie, obsługa profesjonalna.', 'rating' => 5, 'order' => 0],
            ['author_name' => 'Anna Nowak', 'content' => 'Polecam! Szybka rezerwacja, wszystko działało bez problemów.', 'rating' => 5, 'order' => 1],
            ['author_name' => 'Piotr Wiśniewski', 'content' => 'Najlepsza wypożyczalnia w mieście. Na pewno wrócę!', 'rating' => 5, 'order' => 2],
        ];

        foreach ($testimonials as $testimonialData) {
            Testimonial::withoutGlobalScope(TenantScope::class)->create(
                array_merge($testimonialData, [
                    'tenant_id' => $this->tenant->id,
                    'published' => true,
                ])
            );
        }
        $this->info('    ✅ Testimonials utworzone');
    }

    /**
     * Pobiera tenanta (z argumentu lub pierwszego aktywnego).
     */
    private function getTenant(): ?Tenant
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            try {
                return Tenant::where('id', $tenantId)
                    ->where('is_active', true)
                    ->first();
            } catch (\Exception $e) {
                $this->warn("⚠️  Nie można połączyć się z bazą danych: {$e->getMessage()}");

                return null;
            }
        }

        // Pobierz pierwszego aktywnego tenanta
        try {
            return Tenant::where('is_active', true)->first();
        } catch (\Exception $e) {
            $this->warn("⚠️  Nie można połączyć się z bazą danych: {$e->getMessage()}");

            return null;
        }
    }
}
