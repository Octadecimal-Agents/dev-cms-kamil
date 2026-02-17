# Checklist: Porównanie lokalnej wersji z produkcją

**Data:** 2026-01-25  
**Lokalna wersja:** http://localhost:3001  
**Produkcja:** https://2wheels-rental.pl  

---

## ✅ Status serwera

**Serwer uruchomiony:** ✅  
**Port:** 3001 (3000 zajęty)  
**URL:** http://localhost:3001  

**Mock API:** `mock-api-from-prd.json` (dane z produkcji) ✅

---

## 📋 Checklist porównania

### 1. **Header / Nawigacja**
- [ ] Logo wyświetla się poprawnie (`/img-from-prd/logo.png`)
- [ ] Nazwa: "2Wheels Rental" (nie "MotoRent")
- [ ] Linki menu: O nas, Motocykle, Cennik, Kontakt
- [ ] Przycisk "Rezerwuj teraz" działa

### 2. **Hero Section**
- [ ] Tytuł: "Wypożycz motocykl swoich marzeń"
- [ ] Subtitle: "Najlepsze modele, najlepsze ceny, niezapomniane przeżycia"
- [ ] Obraz hero: `/img-from-prd/hero-bike.png`
- [ ] Przyciski: "Zobacz ofertę" i "Rezerwuj teraz"
- [ ] Statystyki: 50+ Motocykli, 1000+ Klientów, 24/7 Wsparcie

### 3. **WhyUs Section**
- [ ] Tytuł: "Dlaczego warto wybrać naszą wypożyczalnię?"
- [ ] 6 cech z ikonami z `/img-from-prd/icons/`
- [ ] "Dogodna lokalizacja" → "Centralnie położona wypożyczalnia w **Tarnowie**" (nie Warszawa)

### 4. **Fleet Section**
- [ ] Tytuł: "Wybierz swój motocykl"
- [ ] Filtry działają (Kategoria, Marka, Cena)
- [ ] Lista motocykli (może być pusta jeśli API nie zwraca danych)

### 5. **HowItWorks Section**
- [ ] Tytuł: "Wypożyczenie w 3 krokach"
- [ ] 3 kroki: Wybierz motocykl, Wypełnij formularz, Odbierz i jedź!

### 6. **Pricing Section**
- [ ] Tytuł: "Nasze ceny"
- [ ] Tabela cennika (może być pusta jeśli brak danych)

### 7. **Terms Section**
- [ ] Tytuł: "Warunki wypożyczenia"
- [ ] 6 punktów: Wymagany wiek, Prawo jazdy, Dokumenty, Kaucja, Ubezpieczenie, Zasady zwrotu
- [ ] Accordion działa (kliknięcie rozszerza/zwija)

### 8. **Gallery Section**
- [ ] Tytuł: "Zobacz nasze motocykle"
- [ ] Galeria zdjęć (może być pusta jeśli brak danych)

### 9. **Testimonials Section**
- [ ] Tytuł: "Co mówią nasi klienci?"
- [ ] Opinie (może być pusta jeśli brak danych)

### 10. **Location Section**
- [ ] Tytuł: "Gdzie nas znajdziesz?"
- [ ] Mapa Google z adresem: **ul. Łokuciewskiego 4a m291, 33-100 Tarnów**
- [ ] Kontakt: Telefon +48 123 456 789, Email kontakt@2wheels-rental.pl
- [ ] Godziny otwarcia: Pn-Pt, Sob, Niedziela

### 11. **ContactForm Section**
- [ ] Tytuł: "Zarezerwuj motocykl"
- [ ] Formularz z polami: Motocykl, Daty, Imię, Email, Telefon, Uwagi
- [ ] Przycisk: "Wyślij rezerwację" (nie "Wyślij wiadomość")

### 12. **Footer**
- [ ] Logo i nazwa: "2Wheels Rental"
- [ ] Opis: "Profesjonalna wypożyczalnia motocykli..."
- [ ] Menu: O nas, Motocykle, Cennik, Kontakt
- [ ] Kontakt: Adres (Tarnów), Telefon, Email
- [ ] Copyright: "© {year} 2Wheels Rental. Wszystkie prawa zastrzeżone."
- [ ] Linki: Polityka prywatności, Regulamin

---

## 🔍 Sprawdzenie w Chrome DevTools

### 1. Otwórz Chrome DevTools
- `F12` lub `Cmd+Option+I` (Mac) / `Ctrl+Shift+I` (Windows)
- Zakładka **Console**

### 2. Sprawdź błędy w konsoli
- [ ] **Brak błędów czerwonych** (Error)
- [ ] **Brak ostrzeżeń żółtych** (Warning) - oprócz standardowych React warnings
- [ ] **Brak 404** dla obrazów
- [ ] **Brak 404** dla fontów
- [ ] **Brak błędów TypeScript/JavaScript**

### 3. Sprawdź Network tab
- [ ] Wszystkie obrazy ładują się (200 OK)
  - `/img-from-prd/logo.png`
  - `/img-from-prd/hero-bike.png`
  - `/img-from-prd/icons/*.png`
- [ ] Brak 404 dla zasobów
- [ ] CSS ładuje się poprawnie

### 4. Sprawdź Elements tab
- [ ] Struktura HTML poprawna
- [ ] Klasy Tailwind CSS zastosowane
- [ ] Obrazy mają poprawne `src` i `alt`

---

## 🎨 Porównanie wizualne

### Side-by-side w Chrome:
1. Otwórz **produkcję** w jednej karcie: https://2wheels-rental.pl
2. Otwórz **lokalną** w drugiej karcie: http://localhost:3001
3. Porównaj sekcja po sekcji:
   - [ ] Header wygląda tak samo
   - [ ] Hero wygląda tak samo
   - [ ] Kolory są takie same
   - [ ] Fonty są takie same
   - [ ] Spacing/marginesy są takie same
   - [ ] Responsywność działa (mobile/tablet/desktop)

---

## 🐛 Typowe problemy do sprawdzenia

### Obrazy:
- [ ] Logo wyświetla się (nie placeholder)
- [ ] Hero image wyświetla się (nie placeholder)
- [ ] Ikony wyświetlają się poprawnie
- [ ] Brak broken images

### Teksty:
- [ ] "Tarnów" zamiast "Warszawa" (w WhyUs i Location)
- [ ] "2Wheels Rental" zamiast "MotoRent" (w Header i Footer)
- [ ] Email: kontakt@2wheels-rental.pl
- [ ] Kod pocztowy: 33-100

### Funkcjonalność:
- [ ] Menu nawigacyjne działa (scroll do sekcji)
- [ ] Formularz działa (walidacja)
- [ ] Accordion w Terms działa (kliknięcie)
- [ ] Filtry w Fleet działają (jeśli są motocykle)

---

## 📊 Raport z porównania

**Po sprawdzeniu, wypełnij:**

```
✅ Działa poprawnie:
- [lista elementów które działają]

❌ Problemy znalezione:
- [lista problemów]

⚠️ Różnice vs produkcja:
- [lista różnic]

🔧 Do naprawy:
- [lista rzeczy do poprawy]
```

---

## 🚀 Quick Test Commands

### Sprawdź czy serwer działa:
```bash
curl http://localhost:3001 | grep -o "2Wheels Rental"
```

### Sprawdź czy obrazy są dostępne:
```bash
curl -I http://localhost:3001/img-from-prd/logo.png
curl -I http://localhost:3001/img-from-prd/hero-bike.png
```

---

**Status:** ✅ Serwer uruchomiony na http://localhost:3001  
**Następny krok:** Otwórz w Chrome i sprawdź zgodnie z checklistą
