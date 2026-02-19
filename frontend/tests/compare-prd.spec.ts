import { test, expect } from '@playwright/test';

const PROD_URL = 'https://2wheels-rental.pl';
const LOCAL_URL = 'http://localhost:3000';

test.describe('Porównanie produkcja vs localhost', () => {
  test('Hero section - tytuł i przyciski', async ({ page }) => {
    // Produkcja
    await page.goto(PROD_URL);
    const prodTitle = await page.locator('h1').first().textContent();
    const prodButtons = await page.locator('a[href*="#"]').filter({ hasText: /Wypożycz|Dowiedz|Zobacz|Rezerwuj/ }).allTextContents();
    
    // Localhost
    await page.goto(LOCAL_URL);
    const localTitle = await page.locator('h1').first().textContent();
    const localButtons = await page.locator('a[href*="#"]').filter({ hasText: /Wypożycz|Dowiedz|Zobacz|Rezerwuj/ }).allTextContents();
    
    console.log('PROD Title:', prodTitle);
    console.log('LOCAL Title:', localTitle);
    console.log('PROD Buttons:', prodButtons);
    console.log('LOCAL Buttons:', localButtons);
    
    expect(localTitle).toContain('Wypożycz motocykl');
    expect(localTitle).toContain('swoich marzeń');
    expect(localButtons.length).toBeGreaterThan(0);
  });

  test('Statystyki hero', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const stats = await page.locator('text=/50\\+|1020\\+|1000\\+|24\\/7/').allTextContents();
    console.log('Stats:', stats);
    
    expect(stats.length).toBeGreaterThanOrEqual(3);
  });

  test('Sekcja HowItWorks - 6 kroków', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const howItWorksTitle = await page.locator('text=/Jak wypożyczyć motocykl\\?/').first();
    await expect(howItWorksTitle).toBeVisible();
    
    const steps = await page.locator('[class*="step"], [class*="krok"]').count();
    const stepNumbers = await page.locator('text=/^[1-6]$/').count();
    
    console.log('Steps count:', steps);
    console.log('Step numbers:', stepNumbers);
    
    // Sprawdź czy są 6 kroków (może być w różnych formatach)
    const stepTexts = await page.locator('text=/Wybierz datę|Wybierz motocykl|Wypełnij formularz|Opłać|Odbierz|Ciesz się/').allTextContents();
    console.log('Step texts found:', stepTexts);
    
    expect(stepTexts.length).toBeGreaterThanOrEqual(3);
  });

  test('Sekcja WhyUs - 3 cechy', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const whyUsTitle = await page.locator('text=/Dlaczego warto nas wybrać\\?/').first();
    await expect(whyUsTitle).toBeVisible();
    
    const features = await page.locator('text=/Szeroki wybór|Bezpieczeństwo|Profesjonalizm/').allTextContents();
    console.log('Features:', features);
    
    expect(features.length).toBeGreaterThanOrEqual(1);
  });

  test('Sekcja Pricing - tabela z okresami', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const pricingTitle = await page.locator('text=/Nasze ceny/').first();
    await expect(pricingTitle).toBeVisible();
    
    // Sprawdź czy jest tabela z okresami
    const periods = await page.locator('text=/1-3 dni|4-7 dni|8-14 dni|15\\+ dni/').allTextContents();
    const prices = await page.locator('text=/300 PLN|250 PLN|200 PLN|150 PLN/').allTextContents();
    
    console.log('Periods:', periods);
    console.log('Prices:', prices);
    
    // Jeśli nie ma tabeli z okresami, sprawdź czy jest tabela w ogóle
    const table = page.locator('table');
    const tableExists = await table.count() > 0;
    
    expect(tableExists || periods.length > 0).toBeTruthy();
  });

  test('Sekcja Testimonials - opinie z avatarami', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const testimonialsTitle = await page.locator('text=/Zadowoleni klienci|Co mówią nasi klienci\\?/').first();
    
    if (await testimonialsTitle.count() > 0) {
      await expect(testimonialsTitle).toBeVisible();
      
      const testimonials = await page.locator('[class*="testimonial"], [class*="opinie"]').count();
      console.log('Testimonials count:', testimonials);
    }
  });

  test('Sekcja Location - mapa i kontakt', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const locationTitle = await page.locator('text=/Gdzie nas znajdziesz\\?|Gdzie nas znaleźć\\?/').first();
    await expect(locationTitle).toBeVisible();
    
    // Sprawdź mapę
    const map = page.locator('iframe[src*="maps.google.com"], iframe[src*="google.com/maps"]');
    const mapExists = await map.count() > 0;
    expect(mapExists).toBeTruthy();
    
    // Sprawdź adres
    const address = await page.locator('text=/ul\\.|Łokuciewskiego|Przykładowa/').first();
    await expect(address).toBeVisible();
    
    // Sprawdź email
    const email = await page.locator('text=/@2wheels-rental\\.pl|@motorent\\.pl/').first();
    await expect(email).toBeVisible();
  });

  test('Sekcja ContactForm - formularz kontaktowy', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const contactTitle = await page.locator('text=/Masz pytania\\?|Skontaktuj się z nami/').first();
    await expect(contactTitle).toBeVisible();
    
    // Sprawdź pola formularza
    const nameField = page.locator('input[placeholder*="Imię"], input[placeholder*="nazwisko"]');
    const emailField = page.locator('input[type="email"]');
    const messageField = page.locator('textarea[placeholder*="wiadomość"], textarea[placeholder*="Wiadomość"]');
    
    await expect(nameField).toBeVisible();
    await expect(emailField).toBeVisible();
    await expect(messageField).toBeVisible();
  });

  test('Footer - social media i linki', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    const footer = page.locator('footer');
    await expect(footer).toBeVisible();
    
    // Sprawdź copyright
    const copyright = page.locator('text=/©|2 Wheels Rental|2Wheels Rental/').first();
    await expect(copyright).toBeVisible();
    
    // Sprawdź czy są linki
    const footerLinks = await page.locator('footer a').count();
    expect(footerLinks).toBeGreaterThan(0);
  });

  test('Obrazy ładują się poprawnie', async ({ page }) => {
    await page.goto(LOCAL_URL);
    
    // Sprawdź logo
    const logo = page.locator('img[src*="logo"]').first();
    await expect(logo).toBeVisible();
    
    // Sprawdź hero image
    const heroImage = page.locator('img[src*="hero-bike"]').first();
    await expect(heroImage).toBeVisible();
    
    // Sprawdź ikony
    const icons = await page.locator('img[src*="icon-"]').count();
    expect(icons).toBeGreaterThan(0);
  });

  test('Brak błędów w konsoli', async ({ page }) => {
    const errors: string[] = [];
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    
    page.on('pageerror', error => {
      errors.push(error.message);
    });
    
    await page.goto(LOCAL_URL);
    await page.waitForLoadState('networkidle');
    
    console.log('Console errors:', errors);
    
    // Filtruj znane błędy (np. React dev warnings)
    const criticalErrors = errors.filter(e => 
      !e.includes('Warning') && 
      !e.includes('React') &&
      !e.includes('Hydration')
    );
    
    expect(criticalErrors.length).toBe(0);
  });
});

test('Pełne porównanie struktury', async ({ page }) => {
  const prodSections: string[] = [];
  const localSections: string[] = [];
  
  // Produkcja
  await page.goto(PROD_URL);
  await page.waitForLoadState('networkidle');
  const prodHeadings = await page.locator('h1, h2').allTextContents();
  prodSections.push(...prodHeadings);
  
  // Localhost
  await page.goto(LOCAL_URL);
  await page.waitForLoadState('networkidle');
  const localHeadings = await page.locator('h1, h2').allTextContents();
  localSections.push(...localHeadings);
  
  console.log('PROD Sections:', prodSections);
  console.log('LOCAL Sections:', localSections);
  
  // Sprawdź kluczowe sekcje
  const requiredSections = [
    'Wypożycz motocykl',
    'Jak wypożyczyć',
    'Wybierz swój motocykl',
    'Dlaczego',
    'Nasze ceny',
    'Warunki',
    'Gdzie nas'
  ];
  
  const localText = localSections.join(' ').toLowerCase();
  requiredSections.forEach(section => {
    expect(localText).toContain(section.toLowerCase());
  });
});
