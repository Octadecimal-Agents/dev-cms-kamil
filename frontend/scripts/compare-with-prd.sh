#!/bin/bash

# Skrypt do porównania localhost z produkcją

PROD_URL="https://2wheels-rental.pl"
LOCAL_URL="http://localhost:3000"

echo "🔍 Porównywanie produkcji vs localhost..."
echo ""

# Sprawdź czy localhost działa
if ! curl -s "$LOCAL_URL" > /dev/null 2>&1; then
  echo "❌ Localhost nie odpowiada na $LOCAL_URL"
  echo "   Uruchom: npm run dev"
  exit 1
fi

echo "✅ Localhost działa"
echo ""

# Porównaj kluczowe elementy
echo "📋 Porównanie kluczowych elementów:"
echo ""

# 1. Hero title
echo "1. Hero Title:"
PROD_TITLE=$(curl -s "$PROD_URL" | grep -o "Wypożycz motocykl" | head -1)
LOCAL_TITLE=$(curl -s "$LOCAL_URL" | grep -o "Wypożycz motocykl" | head -1)
if [ "$PROD_TITLE" = "$LOCAL_TITLE" ]; then
  echo "   ✅ Tytuł zgodny"
else
  echo "   ❌ Tytuł różny: PROD='$PROD_TITLE', LOCAL='$LOCAL_TITLE'"
fi

# 2. Nazwa firmy
echo "2. Nazwa firmy:"
PROD_NAME=$(curl -s "$PROD_URL" | grep -oE "2\s*WHEELS\s*RENTAL|2Wheels\s*Rental" | head -1)
LOCAL_NAME=$(curl -s "$LOCAL_URL" | grep -oE "2\s*WHEELS\s*RENTAL|2Wheels\s*Rental" | head -1)
if [ -n "$PROD_NAME" ] && [ -n "$LOCAL_NAME" ]; then
  echo "   ✅ Nazwa zgodna"
else
  echo "   ⚠️  PROD: '$PROD_NAME', LOCAL: '$LOCAL_NAME'"
fi

# 3. Statystyki
echo "3. Statystyki:"
PROD_STATS=$(curl -s "$PROD_URL" | grep -oE "50\+|1020\+|1000\+|24/7" | sort -u)
LOCAL_STATS=$(curl -s "$LOCAL_URL" | grep -oE "50\+|1020\+|1000\+|24/7" | sort -u)
echo "   PROD: $PROD_STATS"
echo "   LOCAL: $LOCAL_STATS"

# 4. Przyciski Hero
echo "4. Przyciski Hero:"
PROD_BUTTONS=$(curl -s "$PROD_URL" | grep -oE "Wypożycz teraz|Dowiedz się więcej|Zobacz ofertę|Rezerwuj teraz" | sort -u)
LOCAL_BUTTONS=$(curl -s "$LOCAL_URL" | grep -oE "Wypożycz teraz|Dowiedz się więcej|Zobacz ofertę|Rezerwuj teraz" | sort -u)
echo "   PROD: $PROD_BUTTONS"
echo "   LOCAL: $LOCAL_BUTTONS"

# 5. Adres
echo "5. Adres:"
PROD_ADDRESS=$(curl -s "$PROD_URL" | grep -oE "ul\\. [^<]*|Łokuciewskiego|Przykładowa" | head -1)
LOCAL_ADDRESS=$(curl -s "$LOCAL_URL" | grep -oE "ul\\. [^<]*|Łokuciewskiego|Przykładowa" | head -1)
echo "   PROD: $PROD_ADDRESS"
echo "   LOCAL: $LOCAL_ADDRESS"

# 6. Email
echo "6. Email:"
PROD_EMAIL=$(curl -s "$PROD_URL" | grep -oE "info@2wheels-rental\\.pl|kontakt@2wheels-rental\\.pl" | head -1)
LOCAL_EMAIL=$(curl -s "$LOCAL_URL" | grep -oE "info@2wheels-rental\\.pl|kontakt@2wheels-rental\\.pl" | head -1)
echo "   PROD: $PROD_EMAIL"
echo "   LOCAL: $LOCAL_EMAIL"

# 7. Sekcje
echo "7. Sekcje:"
PROD_SECTIONS=$(curl -s "$PROD_URL" | grep -oE "<h[12][^>]*>.*</h[12]>" | sed 's/<[^>]*>//g' | head -10)
LOCAL_SECTIONS=$(curl -s "$LOCAL_URL" | grep -oE "<h[12][^>]*>.*</h[12]>" | sed 's/<[^>]*>//g' | head -10)
echo "   PROD ma sekcje: $(echo "$PROD_SECTIONS" | wc -l)"
echo "   LOCAL ma sekcje: $(echo "$LOCAL_SECTIONS" | wc -l)"

echo ""
echo "✅ Porównanie zakończone"
echo ""
echo "📊 Uruchom pełne testy Playwright:"
echo "   npx playwright test tests/compare-prd.spec.ts"
