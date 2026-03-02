import { test, expect, type Page } from '@playwright/test';

/**
 * E2E Test: Field editing in CMS reflects on frontend.
 *
 * Requires env vars:
 *   E2E_ADMIN_EMAIL    - CMS admin email
 *   E2E_ADMIN_PASSWORD - CMS admin password
 *   E2E_CMS_URL        - CMS URL (default: https://cms.2wheels-rental.pl)
 *   E2E_FRONTEND_URL   - Frontend URL (default: https://2wheels-rental.pl)
 *
 * Run: E2E_ADMIN_EMAIL=x E2E_ADMIN_PASSWORD=y npx playwright test e2e-field-editing
 */

const CMS_URL = process.env.E2E_CMS_URL || 'https://cms.2wheels-rental.pl';
const FRONTEND_URL = process.env.E2E_FRONTEND_URL || 'https://2wheels-rental.pl';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || '';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || '';
const MARKER = 'tst';

test.describe.configure({ mode: 'serial' });

// Skip entire suite if no credentials
test.beforeAll(() => {
  if (!ADMIN_EMAIL || !ADMIN_PASSWORD) {
    test.skip();
  }
});

async function loginToAdmin(page: Page) {
  await page.goto(`${CMS_URL}/admin/login`);
  await page.fill('input[name="email"]', ADMIN_EMAIL);
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**');
}

async function waitForISR() {
  // Wait for ISR revalidation (60s + buffer)
  await new Promise((r) => setTimeout(r, 65_000));
}

test.describe('CMS field editing → frontend reflection', () => {
  // Fields that are simple text and appear on the frontend
  const textFields = [
    { name: 'site_title', selector: 'input[name="data.site_title"]', frontendCheck: 'title' },
    { name: 'site_description', selector: 'textarea[name="data.site_description"]', frontendCheck: 'body' },
  ];

  for (const field of textFields) {
    test(`append "${MARKER}" to ${field.name}, verify on frontend, then revert`, async ({ page }) => {
      test.setTimeout(180_000); // 3 min per field (ISR wait)

      // 1. Login and navigate to site settings edit
      await loginToAdmin(page);
      await page.goto(`${CMS_URL}/admin/modules/content/models/two-wheels/site-settings`);
      await page.waitForLoadState('networkidle');

      // Click edit on the first (only) record
      const editLink = page.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      // 2. Read current value
      const input = page.locator(field.selector);
      const originalValue = await input.inputValue();

      // 3. Append marker
      const modifiedValue = originalValue + MARKER;
      await input.fill(modifiedValue);

      // 4. Save
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // 5. Wait for ISR
      await waitForISR();

      // 6. Check frontend
      const frontendPage = await page.context().newPage();
      await frontendPage.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });

      if (field.frontendCheck === 'title') {
        const pageTitle = await frontendPage.title();
        expect(pageTitle).toContain(MARKER);
      } else {
        const bodyText = await frontendPage.locator('body').textContent();
        expect(bodyText).toContain(MARKER);
      }
      await frontendPage.close();

      // 7. Revert
      await page.goto(`${CMS_URL}/admin/modules/content/models/two-wheels/site-settings`);
      await page.waitForLoadState('networkidle');
      await page.locator('a[href*="/edit"]').first().click();
      await page.waitForLoadState('networkidle');
      await page.locator(field.selector).fill(originalValue);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // 8. Wait for ISR and verify revert
      await waitForISR();

      const verifyPage = await page.context().newPage();
      await verifyPage.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });

      if (field.frontendCheck === 'title') {
        const revertedTitle = await verifyPage.title();
        expect(revertedTitle).not.toContain(MARKER);
      } else {
        const revertedBody = await verifyPage.locator('body').textContent();
        expect(revertedBody).not.toContain(MARKER);
      }
      await verifyPage.close();
    });
  }
});
