# 2Wheels Rental - Środowisko i wdrażanie

## Architektura

| Warstwa | Technologia | Hosting | URL |
|---------|-------------|---------|-----|
| Frontend | Next.js 16 (App Router, SSR/ISR) | Vercel | https://2wheels-rental.pl |
| Backend/CMS | Laravel 12 + Filament 3.3 | OVH shared (cluster121) | https://cms.2wheels-rental.pl |
| API | REST JSON | OVH | https://cms.2wheels-rental.pl/api/2wheels/ |

## Środowiska

### Produkcja

- **Frontend**: https://2wheels-rental.pl (Vercel, auto-deploy z branch `main`)
- **Backend/Admin**: https://cms.2wheels-rental.pl/admin
- **API**: https://cms.2wheels-rental.pl/api/2wheels/
- **OVH path**: `/home/wheelse/2wheels/`
- **Baza**: `wheelse281` (MySQL 8.4)
- **TENANT_ID**: `a0e1ef09-91b0-476a-aec1-45ae89c36bd4`

### Staging

- **Frontend**: https://frontend-git-staging-piotradamczyk8s-projects.vercel.app
- **Backend/Admin**: https://tst.2wheels-rental.pl/admin
- **API**: https://tst.2wheels-rental.pl/api/2wheels/
- **OVH path**: `/home/wheelse/tst/`
- **Baza**: `wheelsetest` (MySQL 8.4)
- **TENANT_ID**: `019c6cfd-55bb-7082-a6c2-e5c07e61ee07`
- **Branch**: `staging`

## Procedura wdrożenia na produkcję

### Skrypt automatyczny

```bash
cd /home/octadecimal/Code/workspaces/clients/kamil/2wheels-rental.pl
./scripts/deploy-production.sh
```

### Flagi skryptu

| Flaga | Opis |
|-------|------|
| `--dry-run` | Symulacja bez wykonywania zmian |
| `--test` | Deploy do test-deploy/ zamiast produkcji |
| `--skip-backup` | Pomiń backup przed deployem |
| `--skip-frontend` | Pomiń push do Vercel |
| `--skip-backend` | Pomiń rsync do OVH |
| `--force` | Pomiń potwierdzenie hasłem |

### Kolejność kroków (automatyczna)

1. **Pre-flight checks** — sprawdza API staging, git status
2. **Autoryzacja** — hasło z Doppler (TWOWHEELS_DEPLOY_PASSWORD)
3. **Merge staging → main** — automatyczny merge
4. **Naprawa URL-i** — zamienia tst.2wheels-rental.pl na cms.2wheels-rental.pl
5. **Backup** — pełna kopia plików + dump bazy MySQL
6. **Rsync backend** — synchronizacja plików do OVH (bez vendor, .env, storage)
7. **Push frontend** — git push origin main (triggeruje Vercel)
8. **Weryfikacja** — curl do API, frontend, storage

### Rollback

Skrypt wyświetla komendę rollback po zakończeniu:
```bash
ssh ovh-2wheels 'rm -rf /home/wheelse/2wheels && cp -r /home/wheelse/backups/2wheels-YYYYMMDD-HHMMSS /home/wheelse/2wheels'
```

## Wdrożenie ręczne

### Backend (OVH)

```bash
# Połączenie SSH
ssh ovh-2wheels

# Lub SCP pojedynczy plik
scp app/Http/Controllers/Api/TwoWheelsController.php ovh-2wheels:/home/wheelse/2wheels/app/Http/Controllers/Api/

# Rsync całość (UWAGA: exclude vendor!)
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='frontend' \
    ./ ovh-2wheels:/home/wheelse/2wheels/
```

### Naprawa symlinku storage

```bash
ssh ovh-2wheels "cd /home/wheelse/2wheels && rm -f public/storage && ln -sf ../storage/app/public public/storage"
```

### Frontend (Vercel)

```bash
git push origin main
# Vercel automatycznie buduje i wdraża (30-60s)

# Jeśli ISR cache nie odświeży:
npx vercel redeploy <deployment-url>
```

## Struktura kodu

### Backend (Laravel)

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── LocationSettings.php      # Dane kontaktowe
│   │   └── ReservationFormSettings.php
│   └── Resources/.../TwoWheels/
│       └── SiteSettingResource.php   # Główne ustawienia strony
├── Http/Controllers/Api/
│   └── TwoWheelsController.php       # REST API
└── Modules/Content/Models/TwoWheels/
    └── SiteSetting.php               # Model
```

### Frontend (Next.js)

```
frontend/
├── app/
│   ├── page.tsx                      # Strona główna (ISR 30s)
│   └── motocykle/[slug]/
│       ├── page.tsx                  # SSR podstrona
│       └── MotorcycleDetailClient.tsx
├── components/
│   ├── DynamicContent.tsx            # Layout sekcji
│   ├── FloatingActions.tsx           # FAB (telefony, WhatsApp)
│   └── sections/
│       ├── ContactForm.tsx           # Formularz kontaktowy
│       └── MotorcycleReservationForm.tsx
└── lib/
    └── api.ts                        # API client i typy
```

## Troubleshooting

### Backend API 500 po deploy

1. Sprawdź czy vendor istnieje: `ssh ovh-2wheels "ls /home/wheelse/2wheels/vendor/"`
2. Jeśli brak, przywróć z backupu lub uruchom composer install (wymaga CLI)
3. Zawsze używaj `--exclude='vendor'` w rsync!

### Obrazy 404 / Storage nie działa

```bash
# Napraw symlink
ssh ovh-2wheels "cd /home/wheelse/2wheels && rm -f public/storage && ln -sf ../storage/app/public public/storage"
```

### Admin panel 500 przy zapisie

1. Sprawdź logi: `ssh ovh-2wheels "tail -30 /home/wheelse/2wheels/storage/logs/laravel.log"`
2. Najczęstsze przyczyny:
   - Brakująca kolumna w bazie → `ALTER TABLE ... ADD COLUMN`
   - Undefined array key → dodaj null coalescing (`?? null`)
   - CSRF error → usuń `->middleware('web')` z API routes

### Frontend nie pokazuje danych

1. Sprawdź TENANT_ID w `frontend/lib/api.ts`
2. Weryfikuj API: `curl https://cms.2wheels-rental.pl/api/2wheels/site-setting`
3. Sprawdź czy Vercel ma aktualne env vars
