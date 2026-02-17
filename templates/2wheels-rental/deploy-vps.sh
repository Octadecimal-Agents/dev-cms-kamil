#!/bin/bash

# Skrypt instalacji szablonu na VPS z obsługą wielu środowisk
# Użycie: ./deploy-vps.sh [env=prd|dev|tst]

set -e

# Kolorowe komunikaty
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parsuj argumenty
ENV="${1#env=}"
if [ -z "$ENV" ] || [ "$ENV" = "$1" ]; then
  ENV="prd"  # Domyślnie production
fi

echo -e "${BLUE}🚀 Instalacja szablonu na VPS - środowisko: ${ENV}${NC}"

# Znajdź katalog projektu (gdzie jest deploy.json)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
DEPLOY_JSON="${PROJECT_DIR}/deploy.json"

# Sprawdź czy deploy.json istnieje
if [ ! -f "$DEPLOY_JSON" ]; then
  echo -e "${RED}❌ deploy.json nie znaleziony w ${PROJECT_DIR}!${NC}"
  exit 1
fi

# Pobierz konfigurację z deploy.json
TEMPLATE_NAME=$(jq -r '.template.name' "$DEPLOY_JSON")
DOMAIN=$(jq -r '.template.domain' "$DEPLOY_JSON")
ENV_CONFIG=$(jq -r ".environments.${ENV}" "$DEPLOY_JSON")

if [ "$ENV_CONFIG" = "null" ]; then
  echo -e "${RED}❌ Środowisko '${ENV}' nie znalezione w deploy.json${NC}"
  exit 1
fi

# Pobierz konfigurację deployment z deploy.json
DEPLOYMENT_PATH=$(jq -r ".environments.${ENV}.deployment.path" "$DEPLOY_JSON")
PM2_NAME=$(jq -r ".environments.${ENV}.pm2.name" "$DEPLOY_JSON")
NGINX_DOMAIN=$(jq -r ".environments.${ENV}.nginx.domain // \"\"" "$DEPLOY_JSON")
API_URL=$(jq -r ".environments.${ENV}.env.NEXT_PUBLIC_API_URL" "$DEPLOY_JSON")

# Jeśli ścieżka nie jest określona, wygeneruj na podstawie konwencji
if [ "$DEPLOYMENT_PATH" = "null" ] || [ -z "$DEPLOYMENT_PATH" ]; then
  if [ "$ENV" = "prd" ]; then
    DEPLOYMENT_PATH="/var/www/${TEMPLATE_NAME}_prd"
  else
    DEPLOYMENT_PATH="/var/www/${TEMPLATE_NAME}_${ENV}"
  fi
fi

# Jeśli domena nie jest określona, wygeneruj na podstawie konwencji
if [ -z "$NGINX_DOMAIN" ]; then
  if [ "$ENV" = "prd" ]; then
    NGINX_DOMAIN="${DOMAIN}"
  else
    NGINX_DOMAIN="${ENV}.${DOMAIN}"
  fi
fi

# Jeśli API URL nie jest określone, wygeneruj na podstawie konwencji
if [ "$API_URL" = "null" ] || [ -z "$API_URL" ]; then
  if [ "$ENV" = "prd" ]; then
    API_PATH="${TEMPLATE_NAME}"
  else
    API_PATH="${TEMPLATE_NAME}_${ENV}"
  fi
  API_URL="https://dev.octadecimal.studio/api/${API_PATH}"
fi

APP_DIR="$DEPLOYMENT_PATH"
DOMAIN_FULL="$NGINX_DOMAIN"
API_DOMAIN="https://dev.octadecimal.studio"

echo -e "${GREEN}📋 Konfiguracja:${NC}"
echo -e "   Template: ${TEMPLATE_NAME}"
echo -e "   Środowisko: ${ENV}"
echo -e "   Katalog: ${APP_DIR}"
echo -e "   Domena: ${DOMAIN_FULL}"
echo -e "   API URL: ${API_URL}"
echo ""

# Sprawdź czy Node.js jest zainstalowany
if ! command -v node &> /dev/null; then
  echo -e "${YELLOW}📦 Instalowanie Node.js 20.x...${NC}"
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
  sudo apt-get install -y nodejs
fi

# Sprawdź czy PM2 jest zainstalowany
if ! command -v pm2 &> /dev/null; then
  echo -e "${YELLOW}📦 Instalowanie PM2...${NC}"
  sudo npm install -g pm2
fi

# Sprawdź czy jq jest zainstalowany (do parsowania JSON)
if ! command -v jq &> /dev/null; then
  echo -e "${YELLOW}📦 Instalowanie jq...${NC}"
  sudo apt-get update && sudo apt-get install -y jq
fi

APP_USER="${USER:-deploy}"

echo -e "${BLUE}📁 Tworzenie katalogu aplikacji: ${APP_DIR}${NC}"
sudo mkdir -p $APP_DIR
sudo chown $APP_USER:$APP_USER $APP_DIR

# Przejdź do katalogu aplikacji
cd $APP_DIR

# Skopiuj pliki projektu (zakładamy że jesteśmy w katalogu projektu)
if [ ! -f "package.json" ]; then
  echo -e "${YELLOW}⚠️  package.json nie znaleziony. Upewnij się, że skopiowałeś wszystkie pliki projektu do ${APP_DIR}${NC}"
  exit 1
fi

echo -e "${BLUE}📦 Instalowanie zależności...${NC}"
npm ci

echo -e "${BLUE}🔧 Konfiguracja zmiennych środowiskowych...${NC}"
REVALIDATE_SECRET=$(openssl rand -hex 32)
TENANT_ID=$(jq -r ".environments.${ENV}.secrets.TENANT_ID // \"a0e1ef09-91b0-476a-aec1-45ae89c36bd4\"" "$DEPLOY_JSON")
# Usuń {{ ask:... }} jeśli występuje
TENANT_ID=$(echo "$TENANT_ID" | sed 's/{{.*}}/a0e1ef09-91b0-476a-aec1-45ae89c36bd4/')

cat > .env.production << EOF
# API Configuration
NEXT_PUBLIC_API_URL=${API_URL}
NEXT_PUBLIC_API_DOMAIN=${API_DOMAIN}
TENANT_ID=${TENANT_ID}

# Environment
NODE_ENV=production
PORT=3000

# Revalidation
REVALIDATE_SECRET=${REVALIDATE_SECRET}
EOF

echo -e "${GREEN}✓ Plik .env.production utworzony${NC}"

echo -e "${BLUE}🏗️  Budowanie aplikacji...${NC}"
npm run build

# Pobierz konfigurację PM2 z deploy.json
PM2_INSTANCES=$(jq -r ".environments.${ENV}.pm2.instances // 2" "$DEPLOY_JSON")
PM2_EXEC_MODE=$(jq -r ".environments.${ENV}.pm2.execMode // \"cluster\"" "$DEPLOY_JSON")

echo -e "${BLUE}🔄 Konfiguracja PM2...${NC}"
cat > ecosystem.config.js << EOF
module.exports = {
  apps: [{
    name: '${PM2_NAME}',
    script: 'node_modules/next/dist/bin/next',
    args: 'start',
    cwd: '${APP_DIR}',
    instances: ${PM2_INSTANCES},
    exec_mode: '${PM2_EXEC_MODE}',
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

# Utwórz katalogi logów
mkdir -p logs

echo -e "${BLUE}🚀 Uruchamianie aplikacji z PM2...${NC}"
# Zatrzymaj istniejącą instancję jeśli istnieje
pm2 delete ${PM2_NAME} 2>/dev/null || true
pm2 start ecosystem.config.js
pm2 save

# Konfiguracja autostartu (tylko raz)
if ! pm2 startup | grep -q "already setup"; then
  echo -e "${YELLOW}⚠️  Uruchom komendę wyświetloną powyżej aby skonfigurować autostart PM2${NC}"
fi

echo -e "${GREEN}✅ Instalacja zakończona!${NC}"
echo ""
echo -e "${GREEN}📊 Status aplikacji:${NC}"
pm2 status

echo ""
echo -e "${GREEN}🌐 Konfiguracja:${NC}"
echo -e "   Katalog: ${APP_DIR}"
echo -e "   Domena: ${DOMAIN_FULL}"
echo -e "   API URL: ${API_URL}"
echo -e "   PM2 Name: ${PM2_NAME}"
echo -e "   Port: 3000"
echo ""
echo -e "${BLUE}📝 Przydatne komendy:${NC}"
echo -e "   pm2 status                    - status aplikacji"
echo -e "   pm2 logs ${PM2_NAME}           - logi aplikacji"
echo -e "   pm2 restart ${PM2_NAME}        - restart aplikacji"
echo -e "   pm2 stop ${PM2_NAME}           - zatrzymanie aplikacji"
echo -e "   pm2 delete ${PM2_NAME}         - usunięcie aplikacji z PM2"
