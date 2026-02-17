# API Integration - 2wheels-rental.pl

**Status:** ✅ Mock API zaimplementowane  
**Data:** 2026-01-23  

---

## 🎯 Cel

Wszystkie teksty i dane w szablonie są teraz pobierane z **mock API** (`data/mock-api.json`), co umożliwia łatwe przejście na prawdziwe Laravel API bez zmian w komponentach.

---

## 📁 Struktura

```
2wheels-rental.pl/
├── data/
│   └── mock-api.json          ← Wszystkie dane (teksty, obrazy, produkty)
├── lib/
│   └── api.ts                 ← Funkcje API + TypeScript types
├── components/
│   ├── Header.tsx             ← ✅ Zrefaktoryzowany (używa props z API)
│   ├── Footer.tsx             ← ✅ Zrefaktoryzowany (używa props z API)
│   └── sections/
│       ├── Hero.tsx           ← ✅ Zrefaktoryzowany (używa props z API)
│       └── WhyUs.tsx          ← ✅ Zrefaktoryzowany (używa props z API)
└── app/
    └── page.tsx               ← ✅ Pobiera dane z API i przekazuje do komponentów
```

---

## 🚀 Jak to działa?

### 1. Dane w JSON

**`data/mock-api.json`:**
```json
{
  "content": {
    "hero": {
      "title": "Wypożycz motocykl",
      "subtitle": "Najlepsze modele, najlepsze ceny"
    },
    "footer": {
      "contact": {
        "phone": "+48 123 456 789",
        "email": "kontakt@motorent.pl"
      }
    }
  }
}
```

### 2. Funkcje API

**`lib/api.ts`:**
```typescript
export async function getHeroData(): Promise<HeroData> {
  return mockData.content.hero;
}

export async function getFooterData(): Promise<FooterData> {
  return mockData.content.footer;
}
```

### 3. Komponenty używają props

**`components/sections/Hero.tsx`:**
```typescript
interface HeroProps {
  hero: HeroData;
}

export default function Hero({ hero }: HeroProps) {
  return (
    <h1>{hero.title}</h1>           {/* z API, nie hardcoded */}
    <p>{hero.subtitle}</p>          {/* z API, nie hardcoded */}
  );
}
```

### 4. Page pobiera dane

**`app/page.tsx`:**
```typescript
export default async function Home() {
  const content = await getAllContent(); // Pobierz wszystkie dane

  return (
    <main>
      <Hero hero={content.hero} />
      <Footer footer={content.footer} />
    </main>
  );
}
```

---

## ✏️ Edycja Danych

### Obecnie (mock API):

**Edytuj `data/mock-api.json`:**
```json
{
  "content": {
    "hero": {
      "title": "NOWY TYTUŁ"  ← zmień tutaj
    }
  }
}
```

**Zapisz → Odśwież stronę → Nowy tytuł widoczny!**

### W przyszłości (Laravel API):

**Edytuj w CMS** → CMS wysyła webhook → Next.js odświeża cache → Nowa treść widoczna!

---

## 🔄 Przejście na Laravel API

**Zmiana tylko w `lib/api.ts`:**

**Przed (mock):**
```typescript
export async function getHeroData(): Promise<HeroData> {
  return mockData.content.hero; // z pliku JSON
}
```

**Po (Laravel):**
```typescript
export async function getHeroData(): Promise<HeroData> {
  const res = await fetch(`${API_URL}/api/v1/content?section=hero`, {
    headers: { 'X-Tenant-ID': process.env.TENANT_ID },
    next: { revalidate: 60 } // Cache 60s
  });
  return res.json();
}
```

**Komponenty NIE WYMAGAJĄ żadnych zmian!** ✅

---

## 📋 Co zostało zrefaktoryzowane?

| Komponent | Status | Dane z API |
|-----------|--------|------------|
| Header | ✅ | Logo, nazwa, linki menu, CTA |
| Hero | ✅ | Tytuł, subtitle, przyciski, statystyki |
| WhyUs | ✅ | Tytuł, subtitle, cechy (6 boxów) |
| Footer | ✅ | Opis, kontakt, menu, linki legal |
| Fleet | ⏳ | Motocykle (produkty) |
| HowItWorks | ⏳ | Kroki (1-4) |
| Pricing | ⏳ | Cennik, dodatki |
| Terms | ⏳ | Warunki wypożyczenia |
| Gallery | ⏳ | Galeria zdjęć |
| Testimonials | ⏳ | Opinie klientów |
| Location | ⏳ | Adres, godziny, mapa |
| ContactForm | ⏳ | Formularz kontaktowy |

**Progress:** 4/12 komponentów (33%)

---

## 🧪 Testowanie

### Lokalnie:
```bash
npm run dev
```

### Edycja danych:
1. Otwórz `data/mock-api.json`
2. Zmień tekst (np. `hero.title`)
3. Zapisz plik
4. Odśwież stronę → Zmiana widoczna!

---

## 💡 Korzyści

✅ **Type Safety** - TypeScript dla wszystkich danych  
✅ **Łatwa edycja** - zmiana JSON bez dotykania kodu  
✅ **Reusability** - komponenty wielokrotnego użytku  
✅ **Easy migration** - przejście na Laravel API bez zmian w komponentach  
✅ **ISR Ready** - Server Components + revalidation  

---

## 🎯 Next Steps

1. ⏳ Dokończyć pozostałe sekcje (Fleet, Pricing, etc.)
2. ⏳ Testowanie lokalnie z mock API
3. ✅ Gdy Laravel API będzie gotowe → zmiana `lib/api.ts`
4. ✅ Deploy z prawdziwym API

---

**Pytania?** Zobacz [MOCK_API_INTEGRATION.md](../MOCK_API_INTEGRATION.md) w głównym katalogu szablonów.
