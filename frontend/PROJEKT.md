
# Projekt: Wypożyczalnia Motocykli

## 🎯 Koncepcja Projektu

Prosta, funkcjonalna strona internetowa dla wypożyczalni motocykli. Styl nowoczesny, dynamiczny, z naciskiem na prezentację pojazdów i łatwy proces rezerwacji.

---

## 🎨 Projekt Graficzny

### Paleta Kolorów

**Główne kolory:**
- **Czarny:** `#000000` - tekst, akcenty
- **Czerwony akcent:** `#DC2626` - CTA, ważne elementy (energia, pasja)
- **Pomarańczowy:** `#F97316` - akcenty, hover states
- **Biały:** `#FFFFFF` - tło
- **Szary jasny:** `#F3F4F6` - tła sekcji
- **Szary ciemny:** `#1F2937` - tekst drugorzędny
- **Szary średni:** `#6B7280` - tekst pomocniczy

**Zasady:**
- Wysoki kontrast dla czytelności
- Dynamiczne, energetyczne kolory
- Ciemne tła dla sekcji z motocyklami (premium feel)

### Typografia

**Font główny:**
- **Inter** - nowoczesny, czytelny, uniwersalny
- Rozmiary: 16px (base), 18px (lead), 14px (small)

**Font nagłówków:**
- **DM Sans** (Bold) lub **Poppins** (Bold) - dynamiczny, mocny
- H1: 56-64px (mobile: 36-40px)
- H2: 40-48px (mobile: 28-32px)
- H3: 24-28px (mobile: 22-24px)

**Zasady:**
- Mocne, wyraźne nagłówki
- Krótkie, dynamiczne teksty
- Duże odstępy między sekcjami

### Layout

**Zasady:**
- **Maksymalna szerokość:** 1400px (dla galerii motocykli)
- **Padding sekcji:** 80-100px (desktop), 40-50px (mobile)
- **Grid:** 12-kolumnowy system
- **Odstępy:** Generous spacing (60-80px między sekcjami)

**Style:**
- Pełnoekranowe sekcje hero
- Karty z cieniami dla motocykli
- Duże, wyraźne CTA
- Dynamiczne animacje (scroll, hover)

---

## 📐 Struktura Strony

### 1. **Header / Nawigacja**
- Logo (lewa strona)
- Menu: O nas | Motocykle | Cennik | Kontakt
- Przycisk CTA: "Rezerwuj teraz" (czerwony, prawa strona)
- Sticky header (przyklejony, półprzezroczysty przy scrollu)
- Mobile: hamburger menu

### 2. **Hero Section**
- **Tło:** Duże zdjęcie motocykla (hero-bike.jpg)
- **Overlay:** Ciemny gradient (czarny → przezroczysty)
- **H1:** "Wypożycz motocykl swoich marzeń"
- **Podtytuł:** "Najlepsze modele, najlepsze ceny, niezapomniane przeżycia"
- **CTA:** Duży przycisk "Zobacz ofertę" (czerwony)
- **Opcjonalnie:** Liczniki (np. "50+ motocykli", "1000+ zadowolonych klientów")

### 3. **Sekcja: Dlaczego My**
- **H2:** "Dlaczego warto wybrać naszą wypożyczalnię?"
- **Grid 3 kolumny** z ikonami:
  - 🏍️ Najnowsze modele
  - ✅ Pełne ubezpieczenie
  - 🛠️ Profesjonalna obsługa
  - 📍 Dogodna lokalizacja
  - 💰 Atrakcyjne ceny
  - ⚡ Szybka rezerwacja

### 4. **Sekcja: Nasza Flota (Główna)**
- **H2:** "Wybierz swój motocykl"
- **Filtry:** Kategoria | Marka | Cena | Dostępność
- **Grid motocykli** (2-3 kolumny):
  - Duże zdjęcie motocykla
  - Nazwa modelu
  - Marka
  - Cena za dzień
  - Kluczowe parametry (pojemność, typ)
  - Przycisk "Szczegóły" / "Rezerwuj"
- **Paginacja** lub "Pokaż więcej"

### 5. **Sekcja: Jak to działa?**
- **H2:** "Wypożyczenie w 3 krokach"
- **Timeline:**
  1. Wybierz motocykl
  2. Wypełnij formularz
  3. Odbierz i jedź!
- **Ikony + krótkie opisy**

### 6. **Sekcja: Cennik**
- **H2:** "Nasze ceny"
- **Tabela cennikowa:**
  - Kategoria motocykla
  - Cena za dzień
  - Cena za tydzień
  - Cena za miesiąc
- **Uwagi:** Ubezpieczenie, kaucja, dodatkowe opcje

### 7. **Sekcja: Warunki wypożyczenia**
- **H2:** "Warunki wypożyczenia"
- **Akordeon** lub lista:
  - Wymagany wiek (min. 21 lat)
  - Prawo jazdy kategorii A
  - Dokumenty potrzebne
  - Kaucja
  - Ubezpieczenie
  - Zasady zwrotu

### 8. **Sekcja: Galeria**
- **H2:** "Zobacz nasze motocykle"
- **Grid zdjęć** (lightbox)
- Różne modele, różne ujęcia

### 9. **Sekcja: Opinie / Referencje**
- **H2:** "Co mówią nasi klienci?"
- **Karty z opiniami:**
  - Zdjęcie klienta (opcjonalnie)
  - Imię
  - Ocena (gwiazdki)
  - Tekst opinii
- **Slider** lub grid

### 10. **Sekcja: Lokalizacja**
- **H2:** "Gdzie nas znajdziesz?"
- **Mapa** (Google Maps embed)
- **Adres:** Pełny adres, telefon, email
- **Godziny otwarcia**

### 11. **Sekcja: Kontakt / Rezerwacja**
- **H2:** "Zarezerwuj motocykl"
- **Formularz:**
  - Wybór motocykla (dropdown)
  - Data odbioru *
  - Data zwrotu *
  - Imię i nazwisko *
  - Email *
  - Telefon *
  - Uwagi
  - Checkbox: Zgoda RODO *
  - Przycisk: "Wyślij zapytanie"
- **Alternatywnie:** Numer telefonu (duży, widoczny)

### 12. **Footer**
- Logo
- Krótki opis
- Linki: O nas | Motocykle | Cennik | Kontakt | Polityka prywatności
- Dane kontaktowe
- Social media (opcjonalnie)
- Copyright

---

## 🛠️ Technologia

### Stack Technologiczny

**Frontend:**
- **Next.js 16** (App Router)
- **TypeScript**
- **Tailwind CSS**
- **React Hook Form** + **Zod** (formularze)
- **Framer Motion** (opcjonalnie - animacje)

**Funkcjonalności:**
- **Filtrowanie motocykli** (kategoria, marka, cena)
- **Formularz rezerwacji** z walidacją
- **Lightbox** dla galerii
- **Responsywny design**

**Deployment:**
- Static export (`output: 'export'`)
- BasePath: `/2wheels-rental.pl.pl` (lub domena)
- Hosting: Statyczny

### Struktura Projektu

```
sites/2wheels-rental.pl/
├── app/
│   ├── layout.tsx
│   ├── page.tsx              # Strona główna
│   ├── motocykle/
│   │   └── page.tsx          # Lista motocykli (opcjonalnie)
│   └── globals.css
├── components/
│   ├── Header.tsx
│   ├── Footer.tsx
│   └── sections/
│       ├── Hero.tsx
│       ├── WhyUs.tsx
│       ├── Fleet.tsx          # Główna sekcja z motocyklami
│       ├── HowItWorks.tsx
│       ├── Pricing.tsx
│       ├── Terms.tsx
│       ├── Gallery.tsx
│       ├── Testimonials.tsx
│       ├── Location.tsx
│       └── ContactForm.tsx
├── components/
│   └── BikeCard.tsx          # Karta pojedynczego motocykla
├── public/
│   └── img/                  # Grafiki (zostaną dostarczone)
│       ├── hero-bike.jpg
│       ├── bike-*.jpg        # Zdjęcia motocykli
│       └── icons/
├── data/
│   └── bikes.json            # Dane motocykli (mock data)
├── next.config.mjs
├── tailwind.config.ts
├── package.json
└── tsconfig.json
```

---

## 📸 Potrzebne Grafiki

### 1. Hero Background
**Plik:** `img/hero-bike.jpg`
**Prompt:**
```
Professional photography of a modern sport motorcycle (Yamaha R1 or similar) 
on a scenic road at golden hour. Dynamic angle, low perspective, motion blur 
in background. Dark, moody atmosphere. High quality, 1920x1080px, cinematic 
lighting. No text, no people. Focus on the motorcycle as the hero element.
```

### 2. Ikony sekcji "Dlaczego My"
**Plik:** `img/icons/`
- `icon-models.svg` - Ikona motocykla (outline, minimalistyczna)
- `icon-insurance.svg` - Ikona tarczy/ubezpieczenia
- `icon-service.svg` - Ikona narzędzi/obsługi
- `icon-location.svg` - Ikona lokalizacji/pinezki
- `icon-price.svg` - Ikona ceny/tag
- `icon-fast.svg` - Ikona błyskawicy/szybkości

**Prompt dla ikon:**
```
Minimalist line icon, black outline, no fill, modern style. 
Simple, clean design suitable for web. SVG format, 64x64px viewport.
```

### 3. Zdjęcia motocykli (przykładowe kategorie)
**Plik:** `img/bikes/`
- `bike-sport-1.jpg` - Sportowy motocykl (Yamaha, Kawasaki, Honda)
- `bike-cruiser-1.jpg` - Cruiser (Harley-Davidson style)
- `bike-touring-1.jpg` - Touring (BMW, Honda Gold Wing)
- `bike-adventure-1.jpg` - Adventure (BMW GS, KTM)
- `bike-naked-1.jpg` - Naked bike (Yamaha MT, KTM Duke)

**Prompt dla zdjęć motocykli:**
```
Professional product photography of [MODEL] motorcycle. 
White or neutral background, studio lighting. Side view, 
showing full bike. High quality, 1200x800px. Clean, 
commercial style. No text, no people. Focus on bike details.
```

**Warianty:**
- Dla każdego motocykla: 1 główne zdjęcie (1200x800px)
- Opcjonalnie: 2-3 dodatkowe ujęcia (różne kąty)

### 4. Placeholder dla opinii
**Plik:** `img/testimonials/`
- `avatar-1.jpg` - Avatar klienta (neutralny, profesjonalny)
- `avatar-2.jpg`
- `avatar-3.jpg`

**Prompt:**
```
Professional headshot portrait, neutral background, 
friendly smile, business casual. Diverse representation. 
Square format, 200x200px. High quality.
```

### 5. Logo (jeśli potrzebne)
**Plik:** `img/logo.svg` lub `logo.png`
**Prompt:**
```
Modern logo design for motorcycle rental company. 
Minimalist, clean, professional. Incorporate subtle 
motorcycle element (wheel, handlebar, or abstract shape). 
Dark color scheme. Vector format preferred.
```

---

## 🎯 Funkcjonalności

### Wymagane (MVP):
✅ Responsywny design  
✅ Lista motocykli z filtrowaniem  
✅ Formularz rezerwacji  
✅ Sekcja cennik  
✅ Kontakt i lokalizacja  
✅ SEO (meta tags, structured data)  

### Opcjonalne (do rozbudowy):
- Szczegóły pojedynczego motocykla (strona osobna)
- Kalendarz dostępności
- System rezerwacji online (backend)
- Integracja z płatnościami
- Blog/aktualności
- System opinii (backend)

---

## 📊 Plan Realizacji

### Faza 1: Setup i Design System (2-3h)
1. Inicjalizacja Next.js + TypeScript
2. Konfiguracja Tailwind CSS
3. Utworzenie struktury katalogów
4. Podstawowy layout (Header, Footer)
5. Design system (kolory, typografia)

### Faza 2: Komponenty Sekcji (5-6h)
1. Hero Section
2. WhyUs (Dlaczego my)
3. Fleet (Główna sekcja - motocykle)
4. BikeCard (komponent karty)
5. HowItWorks
6. Pricing
7. Terms
8. Gallery
9. Testimonials
10. Location
11. ContactForm

### Faza 3: Funkcjonalności (3-4h)
1. Filtrowanie motocykli (kategoria, marka, cena)
2. Formularz rezerwacji (walidacja)
3. Lightbox dla galerii
4. Animacje (scroll, hover)

### Faza 4: Stylowanie i Responsywność (3-4h)
1. Dopracowanie designu
2. Responsywność (mobile, tablet, desktop)
3. Testy na różnych urządzeniach
4. Optymalizacja obrazów

### Faza 5: SEO i Optymalizacja (2-3h)
1. Meta tags
2. Structured data (Schema.org: LocalBusiness, Product)
3. Optymalizacja wydajności
4. Testy cross-browser

### Faza 6: Testy i Wdrożenie (1-2h)
1. Testy funkcjonalności
2. Build i deploy
3. Finalne poprawki

**Łączny czas:** ~16-22 godziny

---

## 📝 Mock Data - Przykładowe Motocykle

```json
{
  "bikes": [
    {
      "id": 1,
      "name": "Yamaha R1",
      "brand": "Yamaha",
      "category": "sport",
      "price": 350,
      "capacity": "998cc",
      "year": 2023,
      "image": "/img/bikes/bike-sport-1.jpg",
      "available": true
    },
    {
      "id": 2,
      "name": "Harley-Davidson Street Glide",
      "brand": "Harley-Davidson",
      "category": "cruiser",
      "price": 400,
      "capacity": "1745cc",
      "year": 2023,
      "image": "/img/bikes/bike-cruiser-1.jpg",
      "available": true
    },
    {
      "id": 3,
      "name": "BMW R 1250 GS",
      "brand": "BMW",
      "category": "adventure",
      "price": 380,
      "capacity": "1254cc",
      "year": 2023,
      "image": "/img/bikes/bike-adventure-1.jpg",
      "available": true
    }
  ]
}
```

---

## ✅ Checklist Realizacji

- [ ] Setup projektu Next.js
- [ ] Konfiguracja Tailwind CSS
- [ ] Utworzenie komponentów sekcji
- [ ] Komponent BikeCard
- [ ] Filtrowanie motocykli
- [ ] Formularz rezerwacji
- [ ] Responsywność (mobile, tablet, desktop)
- [ ] SEO (meta tags, structured data)
- [ ] Optymalizacja obrazów
- [ ] Testy funkcjonalności
- [ ] Build i deploy
- [ ] Finalne poprawki

---

**Data utworzenia:** 2025-01-08  
**Wersja:** 1.0 (MVP)
