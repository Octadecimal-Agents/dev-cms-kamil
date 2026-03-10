#!/usr/bin/env bash
# deploy-production.sh — Pełny deploy 2wheels-rental.pl na produkcję
#
# Użycie: ./scripts/deploy-production.sh [--dry-run] [--test] [--skip-backup] [--skip-frontend] [--skip-backend] [--force]
#
# Opcje testowania:
#   --dry-run   Pokaż co by się stało bez wykonywania (rsync -n)
#   --test      Deploy do /home/wheelse/test-deploy/ zamiast produkcji
#
# Wymagania:
#   - SSH alias: ovh-2wheels
#   - Doppler CLI (dla hasła deploy)
#   - rsync (dla transferu plików)
#
# UWAGA: OVH shared hosting nie ma CLI artisan ani git.
# Deploy odbywa się przez rsync plików.

set -euo pipefail

# ============================================================================
# KONFIGURACJA
# ============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

SSH_HOST="ovh-2wheels"
PROD_DIR="/home/wheelse/2wheels"
BACKUP_DIR="/home/wheelse/backups"

STAGING_BRANCH="staging"
PROD_BRANCH="main"

PROD_BACKEND_URL="https://cms.2wheels-rental.pl"
PROD_FRONTEND_URL="https://2wheels-rental.pl"
PROD_API_URL="https://cms.2wheels-rental.pl/api/2wheels"
STAGING_API_URL="https://tst.2wheels-rental.pl/api/2wheels"

# Tenant IDs
PROD_TENANT_ID="a0e1ef09-91b0-476a-aec1-45ae89c36bd4"
STAGING_TENANT_ID="019c6cfd-55bb-7082-a6c2-e5c07e61ee07"

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Flagi
SKIP_BACKUP=false
SKIP_FRONTEND=false
SKIP_BACKEND=false
FORCE=false
DRY_RUN=false
TEST_MODE=false

# ============================================================================
# FUNKCJE
# ============================================================================

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_ok() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

cleanup_temp() {
    [[ -d "$TEMP_DIR" ]] && rm -rf "$TEMP_DIR"
}

# ============================================================================
# PARSOWANIE ARGUMENTÓW
# ============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true; shift ;;
        --test) TEST_MODE=true; shift ;;
        --skip-backup) SKIP_BACKUP=true; shift ;;
        --skip-frontend) SKIP_FRONTEND=true; shift ;;
        --skip-backend) SKIP_BACKEND=true; shift ;;
        --force) FORCE=true; shift ;;
        --help|-h)
            echo "Użycie: $0 [--dry-run] [--test] [--skip-backup] [--skip-frontend] [--skip-backend] [--force]"
            echo ""
            echo "  --dry-run       Pokaż co by się stało bez wykonywania"
            echo "  --test          Deploy do test-deploy/ zamiast produkcji"
            echo "  --skip-backup   Pomiń backup"
            echo "  --skip-frontend Pomiń deploy frontend (Vercel)"
            echo "  --skip-backend  Pomiń deploy backend (OVH)"
            echo "  --force         Pomiń potwierdzenie hasłem"
            exit 0
            ;;
        *) log_error "Nieznana opcja: $1"; exit 1 ;;
    esac
done

# Jeśli test mode, zmień katalog docelowy
if [[ "$TEST_MODE" == true ]]; then
    PROD_DIR="/home/wheelse/test-deploy"
    log_warn "TEST MODE: Deploy do $PROD_DIR (nie produkcja)"
fi

# Opcje rsync dla dry-run
RSYNC_OPTS="-avz"
if [[ "$DRY_RUN" == true ]]; then
    RSYNC_OPTS="-avzn"  # -n = dry-run
    log_warn "DRY-RUN MODE: Żadne zmiany nie będą wprowadzone"
fi

# ============================================================================
# START
# ============================================================================

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║       2WHEELS-RENTAL.PL — PRODUCTION DEPLOYMENT              ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

cd "$PROJECT_ROOT"

# ── 1. Pre-flight checks ──
log_info "=== KROK 1: Pre-flight checks ==="

# Sprawdź staging API
log_info "Sprawdzam staging API..."
STAGING_OK=$(curl -sf -o /dev/null -w "%{http_code}" "${STAGING_API_URL}/site-setting?tenant_id=${STAGING_TENANT_ID}" 2>/dev/null || echo "000")
if [[ "$STAGING_OK" != "200" ]]; then
    log_error "Staging API nie odpowiada (HTTP $STAGING_OK)"
    log_error "Przetestuj staging przed deploy na produkcję!"
    exit 1
fi
log_ok "Staging API: HTTP 200"

# Sprawdź git status
if [[ -n "$(git status --porcelain --untracked-files=no 2>/dev/null)" ]]; then
    log_error "Uncommitted changes w repo. Zacommituj przed deployem."
    git status --short
    exit 1
fi
log_ok "Git: clean"

# Pobierz najnowsze
git fetch origin --prune --quiet

# Sprawdź różnice
COMMITS_AHEAD=$(git rev-list --count origin/$PROD_BRANCH..origin/$STAGING_BRANCH 2>/dev/null || echo "0")
if [[ "$COMMITS_AHEAD" == "0" ]]; then
    log_warn "Staging nie ma nowych commitów. Nic do deployowania."
    exit 0
fi

log_info "Commity do zdeployowania ($COMMITS_AHEAD):"
git log --oneline origin/$PROD_BRANCH..origin/$STAGING_BRANCH
echo ""

# ── 2. Potwierdzenie hasłem ──
if [[ "$FORCE" == false ]]; then
    log_info "=== KROK 2: Autoryzacja ==="
    echo -e "${YELLOW}UWAGA: To wdroży zmiany na PRODUKCJĘ!${NC}"
    echo "Wpisz hasło deploy (lub Ctrl+C aby przerwać):"
    read -s DEPLOY_PASS

    EXPECTED_PASS=$(doppler secrets get TWOWHEELS_DEPLOY_PASSWORD --plain \
        -p octadecimal-agents -c prd 2>/dev/null || echo "")

    if [[ -z "$EXPECTED_PASS" ]]; then
        log_error "Nie można pobrać hasła z Doppler"
        exit 1
    fi

    if [[ "$DEPLOY_PASS" != "$EXPECTED_PASS" ]]; then
        log_error "Nieprawidłowe hasło"
        exit 1
    fi
    log_ok "Autoryzacja OK"
fi

# ── 3. Merge staging → main ──
log_info "=== KROK 3: Merge staging → main ==="

if [[ "$DRY_RUN" == true ]]; then
    log_warn "DRY-RUN: Pominięto merge (pokazuję co by się zmieniło):"
    git diff --stat origin/$PROD_BRANCH..origin/$STAGING_BRANCH | tail -15
    log_ok "Merge: symulacja OK"
else
    CURRENT_BRANCH=$(git branch --show-current)
    git checkout $PROD_BRANCH --quiet
    git pull origin $PROD_BRANCH --quiet

    log_info "Merguje origin/$STAGING_BRANCH..."
    if ! git merge origin/$STAGING_BRANCH --no-edit; then
        log_error "Merge conflict! Rozwiąż ręcznie i uruchom ponownie."
        exit 1
    fi
    log_ok "Merge OK"
fi

# ── 4. Napraw URL-e stagingowe ──
log_info "=== KROK 4: Naprawa URL-i produkcyjnych ==="

if [[ "$DRY_RUN" == true ]]; then
    # W dry-run sprawdzamy co BY było naprawione
    STAGING_TENANT="019c6cfd-55bb-7082-a6c2-e5c07e61ee07"
    if git show origin/$STAGING_BRANCH:frontend/lib/api.ts 2>/dev/null | grep -q "tst.2wheels-rental.pl"; then
        log_warn "DRY-RUN: Znaleziono staging URL w api.ts (będzie naprawiony)"
    fi
    if git show origin/$STAGING_BRANCH:frontend/vercel.json 2>/dev/null | grep -q "tst.2wheels-rental.pl"; then
        log_warn "DRY-RUN: Znaleziono staging URL w vercel.json (będzie naprawiony)"
    fi
    if git show origin/$STAGING_BRANCH:frontend/lib/api.ts 2>/dev/null | grep -q "$STAGING_TENANT"; then
        log_warn "DRY-RUN: Znaleziono staging TENANT_ID (będzie naprawiony)"
    fi
    log_ok "URL-e: symulacja OK"
else
    URLS_FIXED=false

    # api.ts
    if grep -q "tst.2wheels-rental.pl" frontend/lib/api.ts 2>/dev/null; then
        log_warn "Naprawiam staging URL w api.ts..."
        sed -i "s|https://tst.2wheels-rental.pl|https://cms.2wheels-rental.pl|g" frontend/lib/api.ts
        URLS_FIXED=true
    fi

    # vercel.json
    if grep -q "tst.2wheels-rental.pl" frontend/vercel.json 2>/dev/null; then
        log_warn "Naprawiam staging URL w vercel.json..."
        sed -i "s|https://tst.2wheels-rental.pl|https://cms.2wheels-rental.pl|g" frontend/vercel.json
        URLS_FIXED=true
    fi

    # Staging TENANT_ID → Production TENANT_ID
    STAGING_TENANT="019c6cfd-55bb-7082-a6c2-e5c07e61ee07"
    if grep -q "$STAGING_TENANT" frontend/lib/api.ts 2>/dev/null; then
        log_warn "Naprawiam TENANT_ID w api.ts..."
        sed -i "s|$STAGING_TENANT|$PROD_TENANT_ID|g" frontend/lib/api.ts
        URLS_FIXED=true
    fi

    if [[ "$URLS_FIXED" == true ]]; then
        git add frontend/lib/api.ts frontend/vercel.json 2>/dev/null || true
        git commit -m "fix(deploy): napraw URL-e i TENANT_ID na produkcyjne

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>" --quiet 2>/dev/null || true
        log_ok "URL-e naprawione i zacommitowane"
    else
        log_ok "URL-e już produkcyjne"
    fi
fi

# ── 5. Backup produkcji ──
if [[ "$SKIP_BACKUP" == false && "$SKIP_BACKEND" == false && "$DRY_RUN" == false ]]; then
    log_info "=== KROK 5: Backup produkcji ==="

    BACKUP_NAME="2wheels-$(date +%Y%m%d-%H%M%S)"

    ssh $SSH_HOST "
        mkdir -p $BACKUP_DIR
        cp -r $PROD_DIR $BACKUP_DIR/$BACKUP_NAME
    " || log_warn "Backup plików nie powiódł się (kontynuuję)"

    # Backup bazy
    ssh $SSH_HOST "
        mysqldump -h wheelse281.mysql.db -u wheelse281 wheelse281 \
            > $BACKUP_DIR/$BACKUP_NAME.sql 2>/dev/null
    " && log_ok "Backup: $BACKUP_DIR/$BACKUP_NAME" || log_warn "Backup DB pominięty"
elif [[ "$DRY_RUN" == true && "$SKIP_BACKUP" == false && "$SKIP_BACKEND" == false ]]; then
    log_info "=== KROK 5: Backup produkcji ==="
    log_warn "DRY-RUN: Pominięto backup (byłby utworzony: $BACKUP_DIR/2wheels-YYYYMMDD-HHMMSS)"
fi

# ── 6. Deploy backend (rsync) ──
if [[ "$SKIP_BACKEND" == false ]]; then
    log_info "=== KROK 6: Deploy backend (rsync) ==="

    # Utwórz katalog docelowy jeśli nie istnieje (test mode)
    if [[ "$DRY_RUN" == false ]]; then
        ssh $SSH_HOST "mkdir -p $PROD_DIR" 2>/dev/null || true
    fi

    rsync $RSYNC_OPTS --delete \
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
        "$PROJECT_ROOT/" "$SSH_HOST:$PROD_DIR/" \
        2>&1 | tail -20

    if [[ "$DRY_RUN" == true ]]; then
        log_warn "DRY-RUN: Powyżej lista plików które BY zostały przesłane"
        log_ok "Backend: symulacja OK"
    else
        # Napraw symlink storage
        log_info "Naprawiam symlink storage..."
        ssh $SSH_HOST "
            cd $PROD_DIR
            rm -f public/storage public/._storage 2>/dev/null || true
            ln -sf ../storage/app/public public/storage
            chmod -R 755 storage bootstrap/cache 2>/dev/null || true
        "
        log_ok "Backend zdeployowany"
    fi
fi

# ── 7. Deploy frontend (push → Vercel) ──
if [[ "$SKIP_FRONTEND" == false ]]; then
    log_info "=== KROK 7: Deploy frontend (Vercel) ==="

    if [[ "$DRY_RUN" == true ]]; then
        log_warn "DRY-RUN: Pominięto git push origin $PROD_BRANCH"
        log_ok "Frontend: symulacja OK"
    else
        git push origin $PROD_BRANCH --quiet
        log_ok "Push do main OK — Vercel auto-deploy triggered"

        log_info "Czekam 90s na build Vercel..."
        sleep 90
    fi
fi

# ── 8. Weryfikacja ──
log_info "=== KROK 8: Weryfikacja ==="

BACKEND_OK=$(curl -sf -o /dev/null -w "%{http_code}" "${PROD_API_URL}/site-setting?tenant_id=${PROD_TENANT_ID}" 2>/dev/null || echo "000")
if [[ "$BACKEND_OK" == "200" ]]; then
    log_ok "Backend API: HTTP 200"
else
    log_error "Backend API: HTTP $BACKEND_OK"
fi

FRONTEND_OK=$(curl -sf -o /dev/null -w "%{http_code}" "$PROD_FRONTEND_URL" 2>/dev/null || echo "000")
if [[ "$FRONTEND_OK" == "200" ]]; then
    log_ok "Frontend: HTTP 200"
else
    log_warn "Frontend: HTTP $FRONTEND_OK (może jeszcze się buduje)"
fi

STORAGE_OK=$(curl -sf -o /dev/null -w "%{http_code}" "${PROD_BACKEND_URL}/storage/site-settings/logos/01KHR3SJVPD4SHGH2J1VPTGT2W.JPG" 2>/dev/null || echo "000")
if [[ "$STORAGE_OK" == "200" ]]; then
    log_ok "Storage symlink: OK"
else
    log_warn "Storage: HTTP $STORAGE_OK"
fi

# ── Podsumowanie ──
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    DEPLOYMENT COMPLETE                       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "  Backend:  $PROD_BACKEND_URL"
echo "  Frontend: $PROD_FRONTEND_URL"
echo ""

if [[ "$SKIP_BACKUP" == false && "$SKIP_BACKEND" == false && "$DRY_RUN" == false && -n "${BACKUP_NAME:-}" ]]; then
    echo "  Rollback: ssh $SSH_HOST 'rm -rf $PROD_DIR && cp -r $BACKUP_DIR/$BACKUP_NAME $PROD_DIR'"
fi
echo ""
