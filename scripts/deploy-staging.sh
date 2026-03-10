#!/usr/bin/env bash
# deploy-staging.sh — Deploy do środowiska staging (OVH)
# Może być uruchamiany swobodnie przez agentów.
#
# Użycie: ./scripts/deploy-staging.sh [--skip-frontend]
#
# UWAGA: OVH shared hosting nie ma git/artisan.
# Deploy odbywa się przez rsync.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

SSH_HOST="ovh-2wheels"
STAGING_DIR="/home/wheelse/tst"
STAGING_URL="https://tst.2wheels-rental.pl"
STAGING_API_URL="https://tst.2wheels-rental.pl/api/2wheels"
STAGING_TENANT_ID="019c6cfd-55bb-7082-a6c2-e5c07e61ee07"

SKIP_FRONTEND=false
[[ "${1:-}" == "--skip-frontend" ]] && SKIP_FRONTEND=true

# Kolory
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_ok() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

echo ""
echo "=== 2WHEELS STAGING DEPLOY ==="
echo ""

cd "$PROJECT_ROOT"

# Sprawdź branch
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
log_info "Branch: $CURRENT_BRANCH"
log_info "Target: $STAGING_DIR"

# ── 1. Deploy backend (rsync) ──
log_info "Synchronizuję pliki backend..."

rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/app/public/*' \
    --exclude='frontend' \
    --exclude='docker*' \
    --exclude='scripts' \
    "$PROJECT_ROOT/" "$SSH_HOST:$STAGING_DIR/" \
    2>&1 | tail -3

# Napraw symlink storage
log_info "Naprawiam symlink storage..."
ssh $SSH_HOST "
    cd $STAGING_DIR
    rm -f public/storage 2>/dev/null || true
    ln -sf ../storage/app/public public/storage
    chmod -R 755 storage bootstrap/cache 2>/dev/null || true
"

log_ok "Backend zdeployowany"

# ── 2. Push do staging (Vercel preview) ──
if [[ "$SKIP_FRONTEND" == false ]]; then
    log_info "Push do origin/staging (Vercel preview)..."
    git push origin staging --quiet 2>/dev/null || log_warn "Push pominięty"
    log_ok "Frontend deploy triggered"
fi

# ── 3. Weryfikacja ──
log_info "Weryfikuję staging API..."
sleep 3
HTTP_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "${STAGING_API_URL}/site-setting?tenant_id=${STAGING_TENANT_ID}" 2>/dev/null || echo "000")

if [[ "$HTTP_CODE" == "200" ]]; then
    log_ok "Staging API: HTTP 200"
    echo ""
    echo "=== STAGING DEPLOY SUCCESS ==="
    echo "  Backend: $STAGING_URL"
    echo "  API:     $STAGING_API_URL"
else
    log_warn "Staging API: HTTP $HTTP_CODE"
    echo "  Sprawdź ręcznie: curl -sf '${STAGING_API_URL}/site-setting?tenant_id=${STAGING_TENANT_ID}'"
fi
echo ""
