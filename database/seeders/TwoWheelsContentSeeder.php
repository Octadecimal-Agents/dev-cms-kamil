<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder treści 2Wheels z produkcji dev.octadecimal.studio.
 *
 * Dane pochodzą z backup 20260204_1036_dev.octadecimal.studio.sql.
 * Tabele: brands, categories, motorcycles, features, process_steps, testimonials.
 */
class TwoWheelsContentSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function run(): void
    {
        $this->tenant = Tenant::where('slug', 'demo-studio')->first()
            ?? Tenant::first();

        if (! $this->tenant) {
            $this->command->error('Brak tenanta. Uruchom TenantSeeder.');
            return;
        }

        app()->instance('current_tenant', $this->tenant);
        $tid = $this->tenant->id;

        $this->command->info('Seedowanie treści 2Wheels z produkcji...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->seedBrands($tid);
        $this->seedCategories($tid);
        $this->seedMotorcycles($tid);
        $this->seedFeatures($tid);
        $this->seedProcessSteps($tid);
        $this->seedTestimonials($tid);
        $this->seedSiteSettings($tid);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Treści 2Wheels zaseedowane.');
    }

    private function seedBrands(string $tid): void
    {
        DB::table('two_wheels_motorcycle_brands')->truncate();

        $brands = [
            ['id' => 'a0eb08d0-0435-4c95-b8cd-8c60d2d0713a', 'name' => 'BMW', 'slug' => 'bmw'],
            ['id' => 'a0eb08d0-0712-4c0a-85a8-537f5cdf0072', 'name' => 'Ducati', 'slug' => 'ducati'],
            ['id' => 'a0eb08d0-0783-464c-b261-f1c3a705a9fb', 'name' => 'Yamaha', 'slug' => 'yamaha'],
            ['id' => 'a0eb08d0-07ff-4301-b2c7-cf0924b7052e', 'name' => 'KTM', 'slug' => 'ktm'],
            ['id' => 'a0eb08d0-0864-4d8e-a0b4-7627ffa290e2', 'name' => 'Kawasaki', 'slug' => 'kawasaki'],
            ['id' => 'a0eb08d0-08eb-42a7-b28f-31a7ddeee99c', 'name' => 'Suzuki', 'slug' => 'suzuki'],
            ['id' => 'a0eb08d0-09a3-48da-a4ec-906f6453ad3b', 'name' => 'MRF', 'slug' => 'mrf'],
            ['id' => 'a0eb08d0-0a12-47a0-95e7-dbeb031f8973', 'name' => 'YCF', 'slug' => 'ycf'],
        ];

        $now = '2026-01-25 03:54:16';

        foreach ($brands as $b) {
            DB::table('two_wheels_motorcycle_brands')->insert([
                'id' => $b['id'],
                'tenant_id' => $tid,
                'name' => $b['name'],
                'slug' => $b['slug'],
                'description' => null,
                'logo_id' => null,
                'published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('  Brands: ' . count($brands));
    }

    private function seedCategories(string $tid): void
    {
        DB::table('two_wheels_motorcycle_categories')->truncate();

        $categories = [
            ['id' => 'a0eb08d0-0a70-4406-9ab6-119ef5376712', 'name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Motocykle adventure/enduro'],
            ['id' => 'a0eb08d0-0ae6-41b0-9f9a-7caa1c76e101', 'name' => 'Touring', 'slug' => 'touring', 'description' => 'Motocykle turystyczne'],
            ['id' => 'a0eb08d0-0b3e-4e61-b89a-edbd7bc95870', 'name' => 'Naked', 'slug' => 'naked', 'description' => 'Motocykle naked'],
            ['id' => 'a0eb08d0-0b96-4ebb-a597-ef8776713fa2', 'name' => 'Sport', 'slug' => 'sport', 'description' => 'Motocykle sportowe i pit bike'],
        ];

        $now = '2026-01-25 03:54:16';

        foreach ($categories as $c) {
            DB::table('two_wheels_motorcycle_categories')->insert([
                'id' => $c['id'],
                'tenant_id' => $tid,
                'name' => $c['name'],
                'slug' => $c['slug'],
                'description' => $c['description'],
                'color' => '#dc2626',
                'published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('  Categories: ' . count($categories));
    }

    private function seedMotorcycles(string $tid): void
    {
        DB::table('two_wheels_motorcycle_gallery')->delete();
        DB::table('two_wheels_testimonials')->where('motorcycle_id', '!=', '')->delete();
        DB::table('two_wheels_motorcycles')->truncate();

        $motorcycles = [
            [
                'id' => 'a0eb08d0-0d51-42c6-875c-9219798a97cc',
                'name' => 'KTM 890 Adventure',
                'slug' => 'ktm-890-adventure',
                'brand_id' => 'a0eb08d0-07ff-4301-b2c7-cf0924b7052e', // KTM
                'category_id' => 'a0eb08d0-0ae6-41b0-9f9a-7caa1c76e101', // Touring
                'engine_capacity' => 889, 'year' => 2021,
                'price_per_day' => 320, 'price_per_week' => 600, 'price_per_month' => 7500, 'deposit' => 3000,
                'description' => '<p>KTM 890 Adventure<br><br>"Ready to Race w każdych warunkach." Nasz komentarz: Niskie osadzenie zbiorników paliwa sprawia, że prowadzi się jak po sznurku, nawet gdy jest objuczony bagażem. Elektronika od KTM to światowa czołówka. To nasz faworyt na techniczne, górskie odcinki, gdzie precyzja prowadzenia jest kluczowa.<br><br>Dla kogo: Riderów, którzy szukają technologii i osiągów na każdej nawierzchni</p>',
                'specifications' => '{"power":"136 KM","seats":"2","engine":"889cc Twin","weight":"249 kg"}',
                'available' => true, 'featured' => true,
                'updated_at' => '2026-01-26 01:58:28',
            ],
            [
                'id' => 'a0eb08d0-0e46-46c6-9f32-e8a92597f7b1',
                'name' => 'Ducati Desert X Rally',
                'slug' => 'ducati-desert-x-rally',
                'brand_id' => 'a0eb08d0-0712-4c0a-85a8-537f5cdf0072', // Ducati
                'category_id' => 'a0eb08d0-0a70-4406-9ab6-119ef5376712', // Adventure
                'engine_capacity' => 937, 'year' => 2024,
                'price_per_day' => 420, 'price_per_week' => 750, 'price_per_month' => 9000, 'deposit' => 5000,
                'description' => '<p>Ducati Desert X Rally<br><br>"Włoska sztuka w terenie." Nasz komentarz: To nie jest tylko motocykl, to deklaracja. W wersji Rally z profesjonalnym zawieszeniem KYB, Desert X staje się maszyną do zadań specjalnych. Piękny jak rzeźba, ale twardy jak głaz. Jazda na nim to przeżycie estetyczne i czysta adrenalina.<br><br>Dla kogo: Estetów, którzy nie boją się ubrudzić najdroższego sprzętu w głębokim piachu</p>',
                'specifications' => '{"power":"110 KM","seats":"1","engine":"937cc L-Twin","weight":"223 kg"}',
                'available' => true, 'featured' => true,
                'updated_at' => '2026-01-26 02:53:01',
            ],
            [
                'id' => 'a0eb08d0-0f1a-46f5-83ce-29aac0144f8f',
                'name' => 'Yamaha Tenere 700 Extreme',
                'slug' => 'yamaha-tenere-700-extreme',
                'brand_id' => 'a0eb08d0-0783-464c-b261-f1c3a705a9fb', // Yamaha
                'category_id' => 'a0eb08d0-0a70-4406-9ab6-119ef5376712', // Adventure
                'engine_capacity' => 689, 'year' => 2024,
                'price_per_day' => 320, 'price_per_week' => 600, 'price_per_month' => 7000, 'deposit' => 3000,
                'description' => '<p>Yamaha Tenere 700 Extreme<br><br>"Legenda, która nie zna granic." Nasz komentarz: Wersja Extreme to T7 na sterydach. Wyższe zawieszenie i rajdowy sznyt sprawiają, że to motocykl dla tych, którzy asfalt traktują tylko jako dojazdówkę. Testowaliśmy go w piachu i błocie – jest nie do zdarcia. To maszyna dla prawdziwych purystów off-roadu.<br><br>Dla kogo: Dla tych, którzy chcą poczuć się jak na rajdzie Dakar</p>',
                'specifications' => '{"power":"72 KM","seats":"1","engine":"689cc Twin","weight":"205 kg"}',
                'available' => true, 'featured' => true,
                'updated_at' => '2026-01-26 01:59:31',
            ],
            [
                'id' => 'a0eb08d0-0fe1-45fe-9149-d26f4e88ee71',
                'name' => 'Yamaha Tracer 900 GT',
                'slug' => 'yamaha-tracer-900-gt',
                'brand_id' => 'a0eb08d0-0783-464c-b261-f1c3a705a9fb', // Yamaha
                'category_id' => 'a0eb08d0-0ae6-41b0-9f9a-7caa1c76e101', // Touring
                'engine_capacity' => 847, 'year' => 2020,
                'price_per_day' => 280, 'price_per_week' => 500, 'price_per_month' => 6000, 'deposit' => 2500,
                'description' => '<p>Yamaha Tracer 900 GT<br><br>"Sportowiec w przebraniu podróżnika." Nasz komentarz: Trzy cylindry Crossplane to muzyka dla uszu. W wersji GT masz wszystko: kufry, tempomat i grzane manetki. Przejechaliśmy na nim tysiące kilometrów po europejskich asfaltach i wiemy jedno – ten silnik nigdy się nie nudzi. Szybki, wygodny i niesamowicie uniwersalny.<br><br>Dla kogo: Na dalekie wyprawy we dwoje, gdzie liczy się czas i komfort</p>',
                'specifications' => '{"power":"115 KM","seats":"2","engine":"847cc Triple","weight":"215 kg"}',
                'available' => true, 'featured' => true,
                'updated_at' => '2026-01-26 01:59:49',
            ],
            [
                'id' => 'a0eb08d0-10ad-4e52-b0fa-4a10fb8c2afb',
                'name' => 'BMW M1000R Competition',
                'slug' => 'bmw-m1000r-competition',
                'brand_id' => 'a0eb08d0-0435-4c95-b8cd-8c60d2d0713a', // BMW
                'category_id' => 'a0eb08d0-0b3e-4e61-b89a-edbd7bc95870', // Naked
                'engine_capacity' => 999, 'year' => 2024,
                'price_per_day' => 600, 'price_per_week' => 1000, 'price_per_month' => 14000, 'deposit' => 10000,
                'description' => '<p>BMW M1000R Competition<br><br>"Hiper-naked. Granica możliwości." Nasz komentarz: Ponad 200 koni mechanicznych bez owiewek. Karbonowe koła, skrzydełka dociskowe i technologia z torów WorldSBK. To motocykl, który przesuwa granice fizyki. Każde odkręcenie gazu to teleportacja. Tylko dla ludzi o mocnych nerwach i pewnej ręce.<br><br>Dla kogo: Dla najbardziej wymagających, którzy szukają absolutnego topu inżynierii</p>',
                'specifications' => '{"power":"210 KM","seats":"2","engine":"999cc Inline-4","weight":"199 kg"}',
                'available' => true, 'featured' => true,
                'updated_at' => '2026-01-26 02:51:05',
            ],
            [
                'id' => 'a0eb08d0-1173-4e5f-8c22-c7ebeddf76eb',
                'name' => 'Kawasaki Z650',
                'slug' => 'kawasaki-z650',
                'brand_id' => 'a0eb08d0-0864-4d8e-a0b4-7627ffa290e2', // Kawasaki
                'category_id' => 'a0eb08d0-0b3e-4e61-b89a-edbd7bc95870', // Naked
                'engine_capacity' => 649, 'year' => 2021,
                'price_per_day' => 280, 'price_per_week' => 530, 'price_per_month' => 6000, 'deposit' => 2500,
                'description' => '<p>Kawasaki Z650<br><br>"Moc ukryta w lekkości." Nasz komentarz: Kompaktowy, przewidywalny, a jednocześnie zadziorny. Ten naked to kwintesencja "Z-family". Uwielbiamy go za moment obrotowy, który sprawia, że każde wyjście z zakrętu to czysta frajda. Wybacza błędy początkującym, ale daje mnóstwo radości starym wyjadaczom.<br><br>Dla kogo: Fanów miejskiej dynamiki i krętych, podmiejskich tras</p>',
                'specifications' => '{"power":"67 KM","seats":"2","engine":"649cc Twin","weight":"187 kg"}',
                'available' => true, 'featured' => false,
                'updated_at' => '2026-01-26 01:46:20',
            ],
            [
                'id' => 'a0eb08d0-1252-44ef-b4d4-d9eeab544543',
                'name' => 'Suzuki GSX-S125',
                'slug' => 'suzuki-gsx-s125',
                'brand_id' => 'a0eb08d0-08eb-42a7-b28f-31a7ddeee99c', // Suzuki
                'category_id' => 'a0eb08d0-0b3e-4e61-b89a-edbd7bc95870', // Naked
                'engine_capacity' => 125, 'year' => 2024,
                'price_per_day' => 120, 'price_per_week' => 300, 'price_per_month' => 3000, 'deposit' => 1000,
                'description' => '<p>Suzuki GSX-S125<br><br>"Zwinność drapieżnika na start." Nasz komentarz: Idealny bilet do świata motocykli. Najlżejszy w swojej klasie, z genami legendarnej serii GSX. To nie jest „zwykła 125-tka" – to precyzyjna maszyna do nauki techniki i sprawnego przemykania przez korki. My od niego zaczynaliśmy zarażać pasją naszych bliskich.<br><br>Dla kogo: Posiadacze kat. B (od 3 lat) lub A1, którzy chcą poczuć sportowy pazur Suzuki.</p>',
                'specifications' => '{"power":"15 KM","engine":"125cc Single","weight":"130 kg"}',
                'available' => true, 'featured' => false,
                'updated_at' => '2026-01-26 01:45:59',
            ],
            [
                'id' => 'a0eb08d0-131d-4d87-ab2d-83fec5e344f4',
                'name' => 'PitBike MRF 140 RC-Z',
                'slug' => 'pitbike-mrf-140-rc-z',
                'brand_id' => 'a0eb08d0-09a3-48da-a4ec-906f6453ad3b', // MRF
                'category_id' => 'a0eb08d0-0b96-4ebb-a597-ef8776713fa2', // Sport
                'engine_capacity' => 140, 'year' => 2023,
                'price_per_day' => 120, 'price_per_week' => 200, 'price_per_month' => 3000, 'deposit' => 500,
                'description' => '<p>PitBike: 140 RC-Z&nbsp;<br><br>"Małe koła, wielkie umiejętności." Nasz komentarz: To tutaj dzieje się magia. Na tych maluchach szkolimy naszych kursantów i samych siebie. Nic tak nie uczy kontroli nad motocyklem i "schodzenia na kolano" jak PitBike na torze. Tona zabawy za ułamek ceny gleby na dużym moto.<br><br>Dla kogo: Dla każdego, kto chce poprawić technikę lub po prostu wyszaleć się na torze gokartowym.</p>',
                'specifications' => '{"power":"14 KM","engine":"140cc Single","weight":"70 kg"}',
                'available' => true, 'featured' => false,
                'updated_at' => '2026-01-26 02:54:40',
            ],
            [
                'id' => 'a0eb08d0-13f0-4af0-a7b4-57b8a89f38b6',
                'name' => 'PitBike MRF 140 SM Supermoto',
                'slug' => 'pitbike-mrf-140-sm',
                'brand_id' => 'a0eb08d0-09a3-48da-a4ec-906f6453ad3b', // MRF
                'category_id' => 'a0eb08d0-0b96-4ebb-a597-ef8776713fa2', // Sport
                'engine_capacity' => 140, 'year' => 2022,
                'price_per_day' => 120, 'price_per_week' => 200, 'price_per_month' => 3000, 'deposit' => 500,
                'description' => '<p>PitBike: MRF 140 SM&nbsp;<br><br>"Małe koła, wielkie umiejętności." Nasz komentarz: To tutaj dzieje się magia. Na tych maluchach szkolimy naszych kursantów i samych siebie. Nic tak nie uczy kontroli nad motocyklem i "schodzenia na kolano" jak PitBike na torze. Tona zabawy za ułamek ceny gleby na dużym moto.<br><br>Dla kogo: Dla każdego, kto chce poprawić technikę lub po prostu wyszaleć się na torze gokartowym.</p>',
                'specifications' => '{"power":"14 KM","engine":"140cc Single","weight":"68 kg"}',
                'available' => true, 'featured' => false,
                'updated_at' => '2026-01-26 02:02:13',
            ],
            [
                'id' => 'a0eb08d0-14c3-4595-a239-5c5a852b0b2b',
                'name' => 'PitBike YCF 125 SM',
                'slug' => 'pitbike-ycf-125-sm',
                'brand_id' => 'a0eb08d0-0a12-47a0-95e7-dbeb031f8973', // YCF
                'category_id' => 'a0eb08d0-0b96-4ebb-a597-ef8776713fa2', // Sport
                'engine_capacity' => 125, 'year' => 2022,
                'price_per_day' => 120, 'price_per_week' => 200, 'price_per_month' => 3000, 'deposit' => 500,
                'description' => '<p>PitBike: YCF 125SM<br><br>"Małe koła, wielkie umiejętności." Nasz komentarz: To tutaj dzieje się magia. Na tych maluchach szkolimy naszych kursantów i samych siebie. Nic tak nie uczy kontroli nad motocyklem i "schodzenia na kolano" jak PitBike na torze. Tona zabawy za ułamek ceny gleby na dużym moto.<br><br>Dla kogo: Dla każdego, kto chce poprawić technikę lub po prostu wyszaleć się na torze gokartowym.</p>',
                'specifications' => '{"power":"10 KM","engine":"125cc Single","weight":"67 kg"}',
                'available' => true, 'featured' => false,
                'updated_at' => '2026-01-26 02:57:15',
            ],
        ];

        $created = '2026-01-25 03:54:16';

        foreach ($motorcycles as $m) {
            DB::table('two_wheels_motorcycles')->insert([
                'id' => $m['id'],
                'tenant_id' => $tid,
                'name' => $m['name'],
                'slug' => $m['slug'],
                'brand_id' => $m['brand_id'],
                'category_id' => $m['category_id'],
                'main_image_id' => null,
                'engine_capacity' => $m['engine_capacity'],
                'year' => $m['year'],
                'price_per_day' => $m['price_per_day'],
                'price_per_week' => $m['price_per_week'],
                'price_per_month' => $m['price_per_month'],
                'deposit' => $m['deposit'],
                'description' => $m['description'],
                'specifications' => $m['specifications'],
                'available' => $m['available'],
                'featured' => $m['featured'],
                'published' => true,
                'published_at' => $created,
                'created_at' => $created,
                'updated_at' => $m['updated_at'],
            ]);
        }

        $this->command->info('  Motorcycles: ' . count($motorcycles));
    }

    private function seedFeatures(string $tid): void
    {
        DB::table('two_wheels_features')->truncate();

        $features = [
            [
                'id' => 'a0ecebf5-58a5-4baa-8a7f-cbf5923cd886',
                'title' => 'Najnowsze modele',
                'description' => 'Flota składająca się z najnowszych modeli 2023-2024',
                'order' => 1,
                'created_at' => '2026-01-26 02:25:14',
                'updated_at' => '2026-01-26 02:48:23',
            ],
            [
                'id' => 'a0eceec9-142d-493a-b637-e50275a4e2c5',
                'title' => 'Pełne ubezpieczenie',
                'description' => 'Wszystkie motocykle w pełni ubezpieczone',
                'order' => 2,
                'created_at' => '2026-01-26 02:33:08',
                'updated_at' => '2026-01-26 02:37:28',
            ],
            [
                'id' => 'a0ecef09-8990-4b4e-8346-6568814f820d',
                'title' => 'Profesjonalna obsługa',
                'description' => 'Doświadczony zespół gotowy pomóc w każdej chwili',
                'order' => 0,
                'created_at' => '2026-01-26 02:33:51',
                'updated_at' => '2026-01-26 02:36:52',
            ],
            [
                'id' => 'a0ecef4a-0738-4626-96b3-0b551b3d2bc5',
                'title' => 'Dogodna lokalizacja',
                'description' => 'Centralnie położona wypożyczalnia w Tarnowie',
                'order' => 4,
                'created_at' => '2026-01-26 02:34:33',
                'updated_at' => '2026-01-26 02:34:33',
            ],
            [
                'id' => 'a0ecef87-7d20-47eb-976e-5bf4be66a118',
                'title' => 'Atrakcyjne ceny',
                'description' => 'Najlepsze ceny na rynku, bez ukrytych kosztów',
                'order' => 5,
                'created_at' => '2026-01-26 02:35:13',
                'updated_at' => '2026-01-26 02:37:45',
            ],
            [
                'id' => 'a0ecefda-3507-4570-8303-c9f057db8b0e',
                'title' => 'Szybka rezerwacja',
                'description' => 'Rezerwacja online w kilka minut',
                'order' => 6,
                'created_at' => '2026-01-26 02:36:07',
                'updated_at' => '2026-01-26 02:38:00',
            ],
        ];

        foreach ($features as $f) {
            DB::table('two_wheels_features')->insert([
                'id' => $f['id'],
                'tenant_id' => $tid,
                'title' => $f['title'],
                'description' => $f['description'],
                'icon_id' => null,
                'order' => $f['order'],
                'published' => true,
                'published_at' => '2026-01-25 00:00:00',
                'created_at' => $f['created_at'],
                'updated_at' => $f['updated_at'],
            ]);
        }

        $this->command->info('  Features: ' . count($features));
    }

    private function seedProcessSteps(string $tid): void
    {
        DB::table('two_wheels_process_steps')->truncate();

        $steps = [
            [
                'id' => 'a0ecb85f-abac-45f7-872f-c2ac673e39ee',
                'step_number' => 1,
                'title' => 'Wybierz motocykl',
                'description' => 'Przeglądaj naszą flotę i wybierz motocykl swoich marzeń',
                'icon_name' => 'calendar',
                'created_at' => '2026-01-26 00:01:00',
                'updated_at' => '2026-01-26 02:13:35',
            ],
            [
                'id' => 'a0ecb8a7-2e60-4f83-ba8c-c59d0d165d05',
                'step_number' => 2,
                'title' => 'Wypełnij formularz',
                'description' => 'Podaj daty wypożyczenia i swoje dane kontaktowe',
                'icon_name' => 'notes',
                'created_at' => '2026-01-26 00:01:46',
                'updated_at' => '2026-01-26 00:03:46',
            ],
            [
                'id' => 'a0ecb8e7-5401-451f-8853-027c503cce44',
                'step_number' => 3,
                'title' => 'Odbierz i jedź!',
                'description' => 'Odbierz motocykl w naszej wypożyczalni i ciesz się jazdą',
                'icon_name' => 'key',
                'created_at' => '2026-01-26 00:02:28',
                'updated_at' => '2026-01-26 00:05:52',
            ],
        ];

        foreach ($steps as $s) {
            DB::table('two_wheels_process_steps')->insert([
                'id' => $s['id'],
                'tenant_id' => $tid,
                'step_number' => $s['step_number'],
                'title' => $s['title'],
                'description' => $s['description'],
                'icon_name' => $s['icon_name'],
                'published' => true,
                'published_at' => '2026-01-26 01:03:05',
                'created_at' => $s['created_at'],
                'updated_at' => $s['updated_at'],
            ]);
        }

        $this->command->info('  Process Steps: ' . count($steps));
    }

    private function seedTestimonials(string $tid): void
    {
        DB::table('two_wheels_testimonials')->truncate();

        $testimonials = [
            [
                'id' => 'a0ece90d-5223-4fe6-99f8-ec8b5612ec57',
                'author_name' => 'Marcin Kowalski',
                'content' => 'Świetna wypożyczalnia! Wypożyczyłem Yamaha Tenere 700 na weekend - motocykl w idealnym stanie, profesjonalna obsługa. Na pewno wrócę!',
                'rating' => 5,
                'motorcycle_id' => 'a0eb08d0-0f1a-46f5-83ce-29aac0144f8f', // Tenere 700
                'order' => 1,
                'created_at' => '2026-01-26 02:17:06',
                'updated_at' => '2026-01-26 02:49:14',
            ],
            [
                'id' => 'a0ece95f-0b08-4863-a1e5-38ba08f2b1b2',
                'author_name' => 'Anna Nowak',
                'content' => 'Wypożyczyłam BMW GS na tydzień. Motocykl jak nowy, wszystko działało perfekcyjnie. Polecam każdemu!',
                'rating' => 5,
                'motorcycle_id' => 'a0eb08d0-10ad-4e52-b0fa-4a10fb8c2afb', // BMW M1000R
                'order' => 2,
                'created_at' => '2026-01-26 02:18:00',
                'updated_at' => '2026-01-26 02:18:00',
            ],
            [
                'id' => 'a0ece9a2-211b-4e5c-8808-60da2bd3b9c8',
                'author_name' => 'Piotr Wiśniewski',
                'content' => 'Najlepsza wypożyczalnia w Warszawie. Szybka rezerwacja, przejrzyste warunki, świetne ceny. 10/10!',
                'rating' => 5,
                'motorcycle_id' => 'a0eb08d0-0f1a-46f5-83ce-29aac0144f8f', // Tenere 700
                'order' => 4,
                'created_at' => '2026-01-26 02:18:44',
                'updated_at' => '2026-01-26 02:18:44',
            ],
            [
                'id' => 'a0ece9d8-4a00-4287-b522-d97f306c12f3',
                'author_name' => 'Katarzyna Mazur',
                'content' => 'Zarezerwowałam Ducati Desert X na przejażdżkę po Mazurach. Niesamowite wrażenia! Obsługa bardzo pomocna.',
                'rating' => 5,
                'motorcycle_id' => 'a0eb08d0-0e46-46c6-9f32-e8a92597f7b1', // Desert X
                'order' => 6,
                'created_at' => '2026-01-26 02:19:19',
                'updated_at' => '2026-01-26 02:19:19',
            ],
        ];

        foreach ($testimonials as $t) {
            DB::table('two_wheels_testimonials')->insert([
                'id' => $t['id'],
                'tenant_id' => $tid,
                'author_name' => $t['author_name'],
                'content' => $t['content'],
                'rating' => $t['rating'],
                'motorcycle_id' => $t['motorcycle_id'],
                'order' => $t['order'],
                'published' => true,
                'created_at' => $t['created_at'],
                'updated_at' => $t['updated_at'],
            ]);
        }

        $this->command->info('  Testimonials: ' . count($testimonials));
    }

    private function seedSiteSettings(string $tid): void
    {
        DB::table('two_wheels_site_settings')->truncate();

        DB::table('two_wheels_site_settings')->insert([
            'id' => 'a0ecb1f1-504d-4e0a-882d-0eb1c7a18752',
            'tenant_id' => $tid,
            'site_title' => '2Wheels Rental',
            'site_description' => "Wypożyczalnia stworzona przez motocyklistów dla motocyklistów.\nNie jesteśmy tylko wypożyczalnią. Jesteśmy ludźmi, którzy na dwóch kołach zostawili tysiące kilometrów asfaltu i kurzu. Poczuj wolność na maszynach, które sami kochamy serwisować i prowadzić.\n\nNajlepsze modele, najlepsze ceny, niezapomniane przeżycia",
            'about_us_content' => null,
            'logo_id' => null,
            'contact_phone' => '+48 512 947 131',
            'contact_email' => 'biuro@wip-company.pl',
            'address' => "Do Huty 37c\n33-100 Tarnów, Polska",
            'opening_hours' => "Pon-Pt: 9:00-18:00\nSob: 10:00-15:00",
            'map_coordinates' => '50.00349234551539, 20.97528283684398',
            'created_at' => '2026-01-25 23:43:01',
            'updated_at' => '2026-01-26 03:28:26',
        ]);

        $this->command->info('  Site Settings: 1');
    }
}
