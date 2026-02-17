# Import danych z produkcji (2wheels-rental.pl)

**Data:** 2026-01-25  
**Źródło:** https://2wheels-rental.pl  

---

## 📋 Co zostało zrobione?

### 1. ✅ Pobranie treści ze strony
- Analiza głównej strony
- Analiza podstrony polityki prywatności
- Wydobycie wszystkich tekstów i treści

### 2. ✅ Pobranie obrazów
- Logo: `public/img-from-prd/logo.png` (722KB)
- Hero image: `public/img-from-prd/hero-bike.png` (1.9MB)
- Ikony: `public/img-from-prd/icons/` (6 ikon)
  - icon-fast.png
  - icon-insurance.png
  - icon-location.png
  - icon-models.png
  - icon-price.png
  - icon-service.png

### 3. ✅ Utworzenie mock-api-from-prd.json
- Plik: `data/mock-api-from-prd.json`
- Zawiera wszystkie dane z produkcji
- Ścieżki do obrazów zaktualizowane na `/img-from-prd/`

---

## 📊 Różnice między mock a produkcją

| Element | Mock (mock-api.json) | Produkcja (mock-api-from-prd.json) |
|---------|---------------------|-----------------------------------|
| **Nazwa** | MotoRent | 2Wheels Rental |
| **Lokalizacja** | Warszawa | Tarnów |
| **Kod pocztowy** | 00-000 | 33-100 |
| **Email** | kontakt@motorent.pl | kontakt@2wheels-rental.pl |
| **Logo** | /img/logo.png | /img-from-prd/logo.png |
| **Hero image** | /img/hero-bike.jpg | /img-from-prd/hero-bike.png |
| **Ikony** | /img/icons/ | /img-from-prd/icons/ |
| **Copyright** | © MotoRent | © 2Wheels Rental |

---

## 📁 Struktura plików

```
2wheels-rental.pl/
├── data/
│   ├── mock-api.json              ← Mock dane (testowe)
│   └── mock-api-from-prd.json     ← Dane z produkcji ✅
├── public/
│   └── img-from-prd/              ← Obrazy z produkcji ✅
│       ├── logo.png
│       ├── hero-bike.png
│       └── icons/
│           ├── icon-fast.png
│           ├── icon-insurance.png
│           ├── icon-location.png
│           ├── icon-models.png
│           ├── icon-price.png
│           └── icon-service.png
```

---

## 🔄 Jak użyć danych z produkcji?

### Opcja 1: Zmiana w lib/api.ts

```typescript
// Zmień import z:
import mockData from '@/data/mock-api.json';

// Na:
import mockData from '@/data/mock-api-from-prd.json';
```

### Opcja 2: Zmienna środowiskowa

```typescript
// lib/api.ts
const MOCK_DATA_FILE = process.env.MOCK_API_SOURCE === 'prd' 
  ? '@/data/mock-api-from-prd.json'
  : '@/data/mock-api.json';

import mockData from MOCK_DATA_FILE;
```

### Opcja 3: Funkcja wyboru

```typescript
// lib/api.ts
export async function getAllContent() {
  const usePrd = process.env.USE_PRD_DATA === 'true';
  const data = usePrd 
    ? await import('@/data/mock-api-from-prd.json')
    : await import('@/data/mock-api.json');
  
  return data.default.content;
}
```

---

## 📝 Dane z produkcji

### Kontakt
- **Adres:** ul. Łokuciewskiego 4a m291, 33-100 Tarnów
- **Email:** kontakt@2wheels-rental.pl
- **Telefon:** +48 123 456 789 (placeholder - wymaga aktualizacji)

### Lokalizacja
- **Miasto:** Tarnów (zamiast Warszawa)
- **Kod pocztowy:** 33-100

### Treści
- Wszystkie teksty zgodne z produkcją
- Warunki wypożyczenia szczegółowe (6 punktów)
- Formularz: "Wyślij rezerwację" (zamiast "Wyślij wiadomość")

---

## ⚠️ Uwagi

1. **Telefon:** `+48 123 456 789` wygląda na placeholder - wymaga weryfikacji
2. **Motocykle:** Flota jest pusta w produkcji (ładowanie przez API)
3. **Opinie:** Brak opinii w produkcji (ładowanie przez API)
4. **Cennik:** Brak szczegółów w produkcji (ładowanie przez API)

---

## 🎯 Następne kroki

1. ✅ Dane z produkcji pobrane
2. ✅ Obrazy pobrane
3. ⏳ Weryfikacja telefonu kontaktowego
4. ⏳ Integracja z prawdziwym API (gdy będzie gotowe)
5. ⏳ Testowanie z danymi z produkcji

---

**Status:** ✅ **Dane z produkcji zaimportowane**  
**Pliki:** `mock-api-from-prd.json` + obrazy w `public/img-from-prd/`
