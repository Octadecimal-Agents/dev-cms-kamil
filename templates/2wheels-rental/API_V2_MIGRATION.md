# API v2 Migration - Zgodność z umową API Contract

**Data:** 2026-01-24  
**Status:** ✅ Zaimplementowane  
**Zgodność:** Umowa z MAYA (CMS AI / Backend Lead)

---

## 📋 Zmiany zgodnie z umową API Contract

### 1. **Struktura Content API**

**Było:**
```json
{
  "content": {
    "hero": {...},
    "fleet": {
      "bikes": [...]  // ← bikes inline
    }
  }
}
```

**Jest:**
```json
{
  "content": {
    "sections": {
      "hero": {...},
      "fleet": {
        "title": "...",
        "categories": [...]
        // bikes NIE tutaj
      }
    }
  }
}
```

### 2. **Osobny endpoint dla Motorcycles**

**Nowy endpoint:**
```
GET /api/v1/sites/{slug}/motorcycles
GET /api/v1/sites/{slug}/motorcycles?category=sport&available=true&per_page=12
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "BMW R1250GS",
      "brand": { "id": "...", "name": "BMW" },
      "category": { "id": "...", "name": "Adventure", "slug": "adventure" },
      "price_per_day": 380,
      "deposit": 5000,
      "specs": { "engine": "1254cc", "power": "136 KM" },
      "images": [{ "url": "...", "alt": "..." }],
      "features": ["ABS", "Traction Control"],
      "available": true
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 50 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

---

## 📁 Nowe pliki

### `data/mock-api-v2.json`
- Struktura zgodna z umową: `content.sections.*`
- `fleet` bez `bikes[]`
- Wszystkie sekcje w `sections`

### `data/mock-motorcycles.json`
- Mock danych dla endpointu `/motorcycles`
- Zawiera 5 przykładowych motocykli
- Struktura zgodna z API Contract

---

## 🔄 Zmiany w kodzie

### `lib/api.ts`

**Dodano:**
- Typy `Motorcycle`, `MotorcyclesResponse`, `Brand`, `Category`
- Funkcja `getMotorcycles(params)` z filtrowaniem i paginacją
- Zmieniona struktura: `content.sections.*` zamiast `content.*`

**Usunięto:**
- `bikes[]` z `FleetData`
- Stary typ `Bike` (zastąpiony `Motorcycle`)

### `app/page.tsx`

**Zmieniono:**
```typescript
// Było:
const content = await getAllContent();
<Fleet fleet={content.fleet} />

// Jest:
const [content, motorcycles] = await Promise.all([
  getAllContent(),
  getMotorcycles({ slug: '2wheels-rental.pl', per_page: 20 })
]);
<Fleet fleet={content.fleet} initialBikes={motorcycles.data} totalBikes={motorcycles.meta.total} />
```

### `components/sections/Fleet.tsx`

**Zmieniono:**
- Przyjmuje `initialBikes: Motorcycle[]` i `totalBikes: number` jako props
- Używa `bike.category.slug` zamiast `bike.category`
- Filtrowanie po `category.slug`

### `components/BikeCard.tsx`

**Zmieniono:**
- Przyjmuje `Motorcycle` zamiast `Bike`
- Używa `bike.brand.name`, `bike.category.name`
- Używa `bike.price_per_day` zamiast `bike.price`
- Używa `bike.specs.engine`, `bike.specs.power`
- Używa `bike.images[0].url` zamiast `bike.image`
- Wyświetla `bike.features[]`

### `components/sections/Pricing.tsx`

**Zmieniono:**
- Przyjmuje `Motorcycle[]` zamiast `Bike[]`
- Używa `bike.price_per_day` zamiast `bike.price`
- Używa `bike.category.slug` i `bike.category.name`

### `components/sections/Gallery.tsx`

**Zmieniono:**
- Przyjmuje `bikes: Motorcycle[]` zamiast `fleet: FleetData`
- Zbiera obrazy z `bike.images[]`

### `components/sections/ContactForm.tsx`

**Zmieniono:**
- Przyjmuje `bikes: Motorcycle[]` zamiast `fleet: FleetData`
- (Obecnie nie używa bikes w formularzu, ale prop jest dostępny)

---

## ✅ Korzyści z nowej struktury

1. **Filtrowanie server-side** - backend filtruje, nie frontend
2. **Paginacja** - lazy loading dla dużych kolekcji
3. **Cache granularity** - osobny cache tag dla motorcycles
4. **Mniejszy response** - content bez 50+ motocykli
5. **Lepsze performance** - równoległe fetche w Next.js Server Components

---

## 🧪 Testowanie

### Build:
```bash
npm run build
```
✅ **Status:** Build przechodzi bez błędów

### Dev server:
```bash
npm run dev
```
✅ **Status:** Działa na `localhost:3000`

---

## 📝 Migracja do prawdziwego API

Gdy MAYA dostarczy prawdziwe API:

1. **Zaktualizuj `lib/api.ts`:**
   ```typescript
   // Zmień importy mocków na fetch do prawdziwego API
   const response = await fetch(`/api/v1/sites/${slug}/content`);
   const motorcycles = await fetch(`/api/v1/sites/${slug}/motorcycles?${params}`);
   ```

2. **Dodaj zmienne środowiskowe:**
   ```env
   NEXT_PUBLIC_API_URL=https://dev.octadecimal.studio
   ```

3. **Reszta kodu pozostaje bez zmian!** ✅

---

## 📚 Dokumentacja

- **API Contract:** Zgodność z umową z MAYA
- **Mock files:** `mock-api-v2.json`, `mock-motorcycles.json`
- **Types:** Wszystkie typy w `lib/api.ts`

---

**Status:** ✅ Gotowe do użycia z mockami, gotowe do migracji na prawdziwe API
