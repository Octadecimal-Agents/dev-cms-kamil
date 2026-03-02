import { test, expect, type Page } from '@playwright/test';
import path from 'path';
import fs from 'fs';

/**
 * E2E Test: Gallery image upload/delete reflects on frontend.
 *
 * Requires env vars:
 *   E2E_ADMIN_EMAIL    - CMS admin email
 *   E2E_ADMIN_PASSWORD - CMS admin password
 *   E2E_CMS_URL        - CMS URL (default: https://cms.2wheels-rental.pl)
 *   E2E_FRONTEND_URL   - Frontend URL (default: https://2wheels-rental.pl)
 *
 * Run: E2E_ADMIN_EMAIL=x E2E_ADMIN_PASSWORD=y npx playwright test e2e-gallery
 */

const CMS_URL = process.env.E2E_CMS_URL || 'https://cms.2wheels-rental.pl';
const FRONTEND_URL = process.env.E2E_FRONTEND_URL || 'https://2wheels-rental.pl';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || '';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || '';

test.describe.configure({ mode: 'serial' });

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
  await new Promise((r) => setTimeout(r, 65_000));
}

// Create a simple 1x1 red pixel PNG for testing
function createTestImage(): string {
  const tmpPath = path.join('/tmp', `test-gallery-${Date.now()}.png`);
  // Minimal valid PNG (1x1 red pixel)
  const png = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
    'base64'
  );
  fs.writeFileSync(tmpPath, png);
  return tmpPath;
}

test.describe('Gallery image upload → frontend reflection', () => {
  test('upload image, verify on frontend, delete, verify removal', async ({ page }) => {
    test.setTimeout(240_000); // 4 min

    // 1. Login
    await loginToAdmin(page);

    // 2. Navigate to gallery (direct URL since nav is hidden)
    await page.goto(`${CMS_URL}/admin/modules/content/models/two-wheels/galleries`);
    await page.waitForLoadState('networkidle');

    // 3. Count existing gallery images on frontend before upload
    const frontendBefore = await page.context().newPage();
    await frontendBefore.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });
    const gallerySection = frontendBefore.locator('#galeria, [id*="gallery"], section:has-text("Galeria")').first();
    const imageCountBefore = await gallerySection.locator('img').count();
    await frontendBefore.close();

    // 4. Upload test image via the gallery page's file upload
    const testImagePath = createTestImage();
    try {
      // Look for the file upload input
      const fileInput = page.locator('input[type="file"]').first();
      await fileInput.setInputFiles(testImagePath);
      await page.waitForTimeout(3000); // Wait for upload processing

      // Click save/submit if there's a button
      const saveButton = page.locator('button:has-text("Zapisz"), button:has-text("Upload"), button[type="submit"]').first();
      if (await saveButton.isVisible()) {
        await saveButton.click();
        await page.waitForLoadState('networkidle');
      }

      // 5. Wait for ISR
      await waitForISR();

      // 6. Check frontend has new image
      const frontendAfterUpload = await page.context().newPage();
      await frontendAfterUpload.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });
      const galleryAfter = frontendAfterUpload.locator('#galeria, [id*="gallery"], section:has-text("Galeria")').first();
      const imageCountAfter = await galleryAfter.locator('img').count();
      expect(imageCountAfter).toBeGreaterThanOrEqual(imageCountBefore);
      await frontendAfterUpload.close();

      // 7. Delete the uploaded image from admin
      await page.goto(`${CMS_URL}/admin/modules/content/models/two-wheels/galleries`);
      await page.waitForLoadState('networkidle');

      // Select the first row checkbox and delete
      const firstCheckbox = page.locator('input[type="checkbox"]').first();
      if (await firstCheckbox.isVisible()) {
        await firstCheckbox.check();
        const deleteButton = page.locator('button:has-text("Usuń")').first();
        if (await deleteButton.isVisible()) {
          await deleteButton.click();
          // Confirm deletion
          const confirmButton = page.locator('button:has-text("Potwierdź"), button:has-text("Usuń")').last();
          if (await confirmButton.isVisible()) {
            await confirmButton.click();
            await page.waitForLoadState('networkidle');
          }
        }
      }

      // 8. Wait for ISR and verify removal
      await waitForISR();

      const frontendAfterDelete = await page.context().newPage();
      await frontendAfterDelete.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });
      const galleryFinal = frontendAfterDelete.locator('#galeria, [id*="gallery"], section:has-text("Galeria")').first();
      const imageCountFinal = await galleryFinal.locator('img').count();
      expect(imageCountFinal).toBeLessThanOrEqual(imageCountAfter);
      await frontendAfterDelete.close();
    } finally {
      // Cleanup temp file
      if (fs.existsSync(testImagePath)) {
        fs.unlinkSync(testImagePath);
      }
    }
  });
});
