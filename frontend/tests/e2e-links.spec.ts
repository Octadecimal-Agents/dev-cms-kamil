import { test, expect } from '@playwright/test';

/**
 * E2E Test: Verify all links on the site point to correct destinations.
 *
 * No credentials needed - tests only the public frontend.
 *
 * Run: npx playwright test e2e-links
 */

const FRONTEND_URL = process.env.E2E_FRONTEND_URL || 'https://2wheels-rental.pl';

interface LinkResult {
  href: string;
  text: string;
  status: number | string;
  ok: boolean;
}

test.describe('Link verification', () => {
  test('all links on homepage respond with 200 or valid redirect', async ({ page }) => {
    test.setTimeout(120_000); // 2 min

    await page.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });

    // Collect all <a> hrefs
    const links = await page.locator('a[href]').evaluateAll((anchors) =>
      anchors.map((a) => ({
        href: (a as HTMLAnchorElement).href,
        text: (a as HTMLAnchorElement).textContent?.trim().slice(0, 50) || '',
      }))
    );

    // Deduplicate and categorize
    const seen = new Set<string>();
    const externalLinks: { href: string; text: string }[] = [];
    const internalLinks: { href: string; text: string }[] = [];
    const anchorLinks: { href: string; text: string }[] = [];

    for (const link of links) {
      if (seen.has(link.href)) continue;
      seen.add(link.href);

      if (link.href.startsWith('#') || link.href.includes('/#')) {
        anchorLinks.push(link);
      } else if (link.href.startsWith('mailto:') || link.href.startsWith('tel:')) {
        // Skip mailto/tel links
        continue;
      } else if (link.href.startsWith(FRONTEND_URL) || link.href.startsWith('/')) {
        internalLinks.push(link);
      } else if (link.href.startsWith('http')) {
        externalLinks.push(link);
      }
    }

    console.log(`Found ${internalLinks.length} internal, ${externalLinks.length} external, ${anchorLinks.length} anchor links`);

    const results: LinkResult[] = [];
    const broken: LinkResult[] = [];

    // Check internal links
    for (const link of internalLinks) {
      try {
        const response = await page.request.get(link.href, {
          maxRedirects: 5,
          timeout: 10_000,
        });
        const result: LinkResult = {
          href: link.href,
          text: link.text,
          status: response.status(),
          ok: response.status() < 400,
        };
        results.push(result);
        if (!result.ok) broken.push(result);
      } catch (e) {
        const result: LinkResult = {
          href: link.href,
          text: link.text,
          status: `error: ${(e as Error).message}`,
          ok: false,
        };
        results.push(result);
        broken.push(result);
      }
    }

    // Check external links (with timeout, non-blocking)
    for (const link of externalLinks) {
      try {
        const response = await page.request.get(link.href, {
          maxRedirects: 5,
          timeout: 10_000,
        });
        const result: LinkResult = {
          href: link.href,
          text: link.text,
          status: response.status(),
          ok: response.status() < 400,
        };
        results.push(result);
        if (!result.ok) broken.push(result);
      } catch (e) {
        // External link failures are warnings, not hard failures
        console.warn(`External link unreachable: ${link.href} - ${(e as Error).message}`);
      }
    }

    // Check anchor links point to existing elements
    for (const link of anchorLinks) {
      const hash = link.href.includes('#') ? link.href.split('#').pop() : '';
      if (!hash) continue;

      const elementExists = await page.locator(`[id="${hash}"]`).count();
      if (elementExists === 0) {
        broken.push({
          href: link.href,
          text: link.text,
          status: 'missing anchor target',
          ok: false,
        });
      }
    }

    // Report
    if (broken.length > 0) {
      console.log('Broken links:');
      for (const b of broken) {
        console.log(`  ${b.status} - ${b.href} ("${b.text}")`);
      }
    }

    // Internal broken links are hard failures
    const brokenInternal = broken.filter((b) =>
      typeof b.href === 'string' && (b.href.startsWith(FRONTEND_URL) || b.href.startsWith('/'))
    );
    expect(brokenInternal).toHaveLength(0);
  });

  test('motorcycle detail pages load correctly', async ({ page }) => {
    test.setTimeout(60_000);

    await page.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });

    // Find motorcycle links
    const motoLinks = await page.locator('a[href*="/motocykle/"]').evaluateAll((anchors) =>
      [...new Set(anchors.map((a) => (a as HTMLAnchorElement).href))].slice(0, 5) // Test up to 5
    );

    console.log(`Testing ${motoLinks.length} motorcycle detail pages`);

    for (const href of motoLinks) {
      const response = await page.request.get(href, { timeout: 15_000 });
      expect(response.status(), `${href} should return 200`).toBe(200);
    }
  });

  test('Google Analytics present on all page types', async ({ page }) => {
    const pagesToCheck = [FRONTEND_URL];

    // Find a motorcycle page
    await page.goto(FRONTEND_URL, { waitUntil: 'domcontentloaded' });
    const motoLink = await page.locator('a[href*="/motocykle/"]').first().getAttribute('href');
    if (motoLink) {
      pagesToCheck.push(motoLink.startsWith('http') ? motoLink : `${FRONTEND_URL}${motoLink}`);
    }

    for (const url of pagesToCheck) {
      const response = await page.request.get(url);
      const html = await response.text();
      expect(html, `GA tag missing on ${url}`).toContain('G-1ECC3WCDN1');
    }
  });
});
