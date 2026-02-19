# Instalacja szablonu 2wheels-rental.pl na VPS

## Wymagania
- Ubuntu 20.04+ / Debian 11+
- Dostęp root/sudo
- Minimum 2GB RAM
- Node.js 20.x
- PM2

## Krok 1: Przygotowanie serwera

```bash
# Aktualizacja systemu
sudo apt update && sudo apt upgrade -y

# Instalacja podstawowych narzędzi
sudo apt install -y curl git build-essential
```

## Krok 2: Instalacja Node.js 20.x

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Sprawdź wersję
node --version  # powinno być v20.x.x
npm --version
```

## Krok 3: Instalacja PM2

```bash
sudo npm install -g pm2
```

## Krok 4: Przygotowanie katalogu aplikacji

```bash
# Utwórz katalog
sudo mkdir -p /var/www/2wheels-rental.pl
sudo chown $USER:$USER /var/www/2wheels-rental.pl
cd /var/www/2wheels-rental.pl
```

## Krok 5: Skopiowanie plików projektu

**Opcja A: Z lokalnego komputera (SCP)**
```bash
# Z lokalnego komputera:
scp -r /Users/piotradamczyk/Projects/OctadecimalStudio/next-templates/2wheels-rental.pl/* user@VPS_IP:/var/www/2wheels-rental.pl/
```

**Opcja B: Z Git (jeśli repo jest dostępne)**
```bash
cd /var/www/2wheels-rental.pl
git clone <repo-url> .
```

**Opcja C: Ręczne skopiowanie**
Skopiuj wszystkie pliki z katalogu projektu do `/var/www/2wheels-rental.pl/`

## Krok 6: Instalacja zależności i konfiguracja

```bash
cd /var/www/2wheels-rental.pl

# Instalacja zależności
npm ci --production

# Utworzenie pliku .env.production
cat > .env.production << 'EOF'
# API Configuration
NEXT_PUBLIC_API_URL=https://dev.octadecimal.studio/api/2wheels
NEXT_PUBLIC_API_DOMAIN=https://dev.octadecimal.studio
TENANT_ID=a0e1ef09-91b0-476a-aec1-45ae89c36bd4

# Environment
NODE_ENV=production
PORT=3000

# Revalidation
REVALIDATE_SECRET=$(openssl rand -hex 32)
EOF

# Generowanie REVALIDATE_SECRET
REVALIDATE_SECRET=$(openssl rand -hex 32)
sed -i "s/\$(openssl rand -hex 32)/$REVALIDATE_SECRET/" .env.production
```

## Krok 7: Budowanie aplikacji

```bash
npm run build
```

## Krok 8: Konfiguracja PM2

```bash
# Utworzenie pliku konfiguracyjnego PM2
cat > ecosystem.config.js << 'EOF'
module.exports = {
  apps: [{
    name: '2wheels-rental.pl',
    script: 'node_modules/next/dist/bin/next',
    args: 'start',
    cwd: '/var/www/2wheels-rental.pl',
    instances: 2,
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    max_memory_restart: '1G'
  }]
};
EOF

# Utworzenie katalogu logów
mkdir -p logs
```

## Krok 9: Uruchomienie aplikacji

```bash
# Uruchomienie z PM2
pm2 start ecosystem.config.js

# Zapisanie konfiguracji PM2 (automatyczne uruchamianie po restarcie)
pm2 save

# Konfiguracja autostartu PM2
pm2 startup
# Wykonaj komendę wyświetloną przez PM2
```

## Krok 10: Sprawdzenie statusu

```bash
# Status aplikacji
pm2 status

# Logi aplikacji
pm2 logs 2wheels-rental.pl

# Sprawdzenie portu
sudo netstat -tlnp | grep 3000
# lub
sudo ss -tlnp | grep 3000
```

## Sprawdzenie IP i portu

```bash
# Sprawdź IP serwera
hostname -I

# Sprawdź czy aplikacja działa
curl http://localhost:3000
```

Aplikacja będzie dostępna pod adresem: `http://IP_SERWERA:3000`

## Przydatne komendy PM2

```bash
pm2 status              # Status wszystkich aplikacji
pm2 logs                # Logi wszystkich aplikacji
pm2 logs 2wheels-rental.pl  # Logi konkretnej aplikacji
pm2 restart all         # Restart wszystkich aplikacji
pm2 restart 2wheels-rental.pl  # Restart konkretnej aplikacji
pm2 stop all            # Zatrzymanie wszystkich aplikacji
pm2 delete all          # Usunięcie wszystkich aplikacji z PM2
pm2 monit               # Monitor w czasie rzeczywistym
```

## Konfiguracja firewall (opcjonalnie)

```bash
# UFW (jeśli używany)
sudo ufw allow 3000/tcp
sudo ufw reload

# Firewalld (jeśli używany)
sudo firewall-cmd --permanent --add-port=3000/tcp
sudo firewall-cmd --reload
```
