Jesteś moim senior software engineerem. Masz zrobić mały generator obrazów z OpenAI API.

Kontekst:
- W projekcie mam plik (np. prompts.json / prompts.yaml / prompts.md) z listą promptów do grafiki.
- Mam klucz OPENAI_API_KEY (ma być brany z env, nie hardkoduj).
- Chcę wygenerować obrazy i zapisać je do katalogu output/ (tworząc go jeśli nie istnieje).
- Każdy prompt ma mieć swój plik wynikowy z przewidywalną nazwą.
- Bez UI, tylko skrypt CLI.
- Kod ma działać na macOS, Node 18+.

Wymagania:
1) Wybierz Node.js jako główne rozwiązanie (TypeScript jeśli repo już go używa, inaczej czysty JS).
2) Użyj oficjalnego SDK "openai" i endpointu Images API (client.images.generate).
   - Model: "gpt-image-1.5"
   - n: 1
   - size: "1024x1024" (lub możliwość zmiany parametrem CLI)
   - format: png (domyślnie)
   - Odbierz base64 z img.data[0].b64_json i zapisz jako plik binarny.
3) Zaimplementuj:
   - parsowanie pliku z promptami:
     - jeśli to JSON: [{ "name": "...", "prompt": "..." }, ...]
     - jeśli to MD/TXT: jedna linia = jeden prompt, name generuj slugiem z pierwszych słów.
   - slugify do nazw plików + numer porządkowy, np. 001_motorcycle-hero.png
   - retry (np. 3 próby) + backoff, oraz logowanie błędów
   - limit równoległości (np. max 2 równocześnie), żeby nie zabić limitów
4) Dodaj instrukcję uruchomienia:
   - npm init / npm i openai
   - export OPENAI_API_KEY=...
   - node scripts/generate-images.js --input prompts.json --out output --size 1024x1024
5) Upewnij się, że skrypt:
   - nie zapisuje nigdzie klucza
   - nie generuje pustych plików gdy API zwróci błąd
   - wypisuje podsumowanie (ile wygenerowano, ile błędów)
6) Stwórz pliki w repo:
   - scripts/generate-images.js (lub .ts)
   - przykładowy prompts.sample.json
   - README krótkie (3–6 linijek) w docs/IMAGE_GENERATION.md

Najpierw:
- zrób szybki plan kroków
- potem wstaw kompletne pliki z kodem (całe treści plików).
- na końcu pokaż komendy do uruchomienia.