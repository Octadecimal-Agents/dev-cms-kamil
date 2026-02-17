# Debug: Problem z obrazami

**Problem:** Obrazy zniknęły na localhost:3000  
**Status:** Sprawdzanie...

---

## ✅ Co działa:

1. **Mock API:** Używa `mock-api-from-prd.json` ✅
2. **Dane:** Adres Tarnów, nazwa 2Wheels Rental ✅
3. **Obrazy dostępne:** HTTP 200 OK ✅
4. **Ścieżki w HTML:** `/img-from-prd/logo.png` ✅

---

## 🔍 Diagnostyka:

### 1. Sprawdź w Chrome DevTools:

**Console:**
- Otwórz F12 → Console
- Sprawdź błędy 404 dla obrazów
- Sprawdź czy są błędy Next.js Image optimization

**Network:**
- F12 → Network → Images
- Odśwież stronę (Cmd+R)
- Sprawdź status każdego obrazu:
  - `/img-from-prd/logo.png` → powinno być 200
  - `/img-from-prd/hero-bike.png` → powinno być 200
  - `/img-from-prd/icons/*.png` → powinno być 200

### 2. Sprawdź cache przeglądarki:

**Hard refresh:**
- Mac: `Cmd+Shift+R`
- Windows: `Ctrl+Shift+R`

**Lub:**
- F12 → Network → Disable cache (checkbox)
- Odśwież stronę

### 3. Sprawdź Next.js Image optimization:

Next.js Image component może mieć problem z:
- Dużymi plikami PNG
- Optymalizacją obrazów
- Cache Next.js

**Rozwiązanie:** Sprawdź czy obrazy są w `public/` (są ✅)

---

## 🛠️ Możliwe rozwiązania:

### 1. Restart serwera dev:
```bash
# Zatrzymaj serwer (Ctrl+C)
# Uruchom ponownie:
npm run dev
```

### 2. Wyczyść cache Next.js:
```bash
rm -rf .next
npm run dev
```

### 3. Sprawdź konfigurację next.config.mjs:
- Czy `images` config jest poprawny?
- Czy nie ma blokady dla PNG?

### 4. Sprawdź czy obrazy są w public/:
```bash
ls -la public/img-from-prd/
```

---

## 📋 Quick Test:

**W terminalu:**
```bash
# Sprawdź czy obrazy są dostępne:
curl -I http://localhost:3000/img-from-prd/logo.png
curl -I http://localhost:3000/img-from-prd/hero-bike.png
```

**Oczekiwany wynik:** `HTTP/1.1 200 OK`

---

## 🎯 Następne kroki:

1. Sprawdź Chrome DevTools → Console (błędy?)
2. Sprawdź Chrome DevTools → Network → Images (status?)
3. Hard refresh (Cmd+Shift+R)
4. Jeśli nie działa → restart serwera + clear cache

---

**Status:** Obrazy są dostępne (200 OK), problem może być w cache przeglądarki lub Next.js Image optimization
