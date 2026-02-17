# Fix: Obrazy nie wyświetlają się

**Problem:** Obrazy zniknęły na localhost:3000  
**Status:** Obrazy są dostępne (200 OK), problem w cache przeglądarki  

---

## ✅ Co działa:

- Mock API: `mock-api-from-prd.json` ✅
- Adres: ul. Łokuciewskiego 4a m291, 33-100 Tarnów ✅
- Email: kontakt@2wheels-rental.pl ✅
- Nazwa: 2Wheels Rental ✅
- Obrazy dostępne: HTTP 200 OK ✅

---

## 🔧 Rozwiązanie - Hard Refresh:

### W Chrome:

1. **Hard Refresh:**
   - Mac: `Cmd + Shift + R`
   - Windows: `Ctrl + Shift + R`

2. **Lub DevTools:**
   - F12 → Network tab
   - Zaznacz "Disable cache" (checkbox)
   - Odśwież stronę (F5)

3. **Lub:**
   - F12 → Application → Clear storage
   - Kliknij "Clear site data"
   - Odśwież stronę

---

## 🧪 Test w DevTools:

### 1. Console (F12 → Console):
- Sprawdź czy są błędy 404 dla obrazów
- Sprawdź czy są błędy Next.js Image

### 2. Network (F12 → Network → Images):
- Odśwież stronę
- Sprawdź status każdego obrazu:
  - `/img-from-prd/logo.png` → 200 OK?
  - `/img-from-prd/hero-bike.png` → 200 OK?
  - `/img-from-prd/icons/*.png` → 200 OK?

### 3. Jeśli 404:
- Sprawdź czy pliki są w `public/img-from-prd/`
- Sprawdź czy serwer dev działa

---

## 🔄 Restart serwera (jeśli hard refresh nie pomaga):

```bash
# Zatrzymaj serwer (Ctrl+C w terminalu)
# Uruchom ponownie:
cd 2wheels-rental.pl
rm -rf .next
npm run dev
```

---

## 📋 Checklist:

- [ ] Hard refresh (Cmd+Shift+R)
- [ ] DevTools → Network → Disable cache
- [ ] Sprawdź Console (błędy?)
- [ ] Sprawdź Network → Images (status?)
- [ ] Restart serwera (jeśli potrzeba)

---

**Status:** Obrazy są dostępne, problem w cache przeglądarki  
**Rozwiązanie:** Hard refresh (Cmd+Shift+R)
