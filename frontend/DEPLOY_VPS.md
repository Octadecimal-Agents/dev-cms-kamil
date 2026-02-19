# Instalacja na VPS - Instrukcja

## Konfiguracja środowisk

Skrypt `deploy-vps.sh` automatycznie konfiguruje środowiska na podstawie `deploy.json`.

### Struktura katalogów i domen

#### Production (prd)
- **Katalog:** `/var/www/2wheels_prd`
- **Domena:** `2wheels-rental.pl` (bez prefixu)
- **API:** `dev.octadecimal.studio/api/2wheels` (bez suffixu)
- **PM2:** `2wheels-prd`

#### Development (dev)
- **Katalog:** `/var/www/2wheels_dev`
- **Domena:** `dev.2wheels-rental.pl`
- **API:** `dev.octadecimal.studio/api/2wheels_dev`
- **PM2:** `2wheels-dev`

#### Test (tst)
- **Katalog:** `/var/www/2wheels_tst`
- **Domena:** `tst.2wheels-rental.pl`
- **API:** `dev.octadecimal.studio/api/2wheels_tst`
- **PM2:** `2wheels-tst`

## Użycie

```bash
# Instalacja środowiska produkcyjnego (domyślnie)
./deploy-vps.sh
# lub
./deploy-vps.sh env=prd

# Instalacja środowiska deweloperskiego
./deploy-vps.sh env=dev

# Instalacja środowiska testowego
./deploy-vps.sh env=tst
```

## Wymagania

- Node.js 20.x
- PM2
- jq (do parsowania JSON)
- Dostęp sudo

## Proces instalacji

1. Sprawdza i instaluje Node.js 20.x (jeśli brak)
2. Sprawdza i instaluje PM2 (jeśli brak)
3. Sprawdza i instaluje jq (jeśli brak)
4. Czyta konfigurację z `deploy.json`
5. Tworzy katalog `/var/www/{nazwa}_{env}`
6. Instaluje zależności (`npm ci`)
7. Tworzy `.env.production` z odpowiednimi zmiennymi
8. Buduje aplikację (`npm run build`)
9. Konfiguruje PM2
10. Uruchamia aplikację

## Konfiguracja deploy.json

```json
{
  "template": {
    "name": "2wheels",
    "domain": "2wheels-rental.pl"
  },
  "environments": {
    "prd": {
      "deployment": {
        "path": "/var/www/2wheels_prd"
      },
      "env": {
        "NEXT_PUBLIC_API_URL": "https://dev.octadecimal.studio/api/2wheels"
      },
      "nginx": {
        "domain": "2wheels-rental.pl"
      }
    },
    "dev": {
      "deployment": {
        "path": "/var/www/2wheels_dev"
      },
      "env": {
        "NEXT_PUBLIC_API_URL": "https://dev.octadecimal.studio/api/2wheels_dev"
      },
      "nginx": {
        "domain": "dev.2wheels-rental.pl"
      }
    }
  }
}
```

## Zarządzanie aplikacją

```bash
# Status
pm2 status

# Logi
pm2 logs 2wheels-prd

# Restart
pm2 restart 2wheels-prd

# Stop
pm2 stop 2wheels-prd

# Delete
pm2 delete 2wheels-prd
```
