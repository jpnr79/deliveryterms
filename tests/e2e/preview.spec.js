const { test, expect } = require('@playwright/test');

// Usage:
// GLPI_URL, GLPI_UI_USER, GLPI_UI_PASS, GLPI_TEMPLATE_ID
// Example:
// GLPI_URL=http://127.0.0.1:8000 GLPI_UI_USER=glpi GLPI_UI_PASS=glpi GLPI_TEMPLATE_ID=1 npx playwright test tests/e2e/preview.spec.js

const BASE = process.env.GLPI_URL || 'http://127.0.0.1:8000';
const USER = process.env.GLPI_UI_USER || 'glpi';
const PASS = process.env.GLPI_UI_PASS || 'glpi';
const TEMPLATE_ID = process.env.GLPI_TEMPLATE_ID || '1';

test.describe('Deliveryterms preview modal', () => {
  test.setTimeout(30_000);

  test('Preview button generates PDF and opens modal iframe', async ({ page }) => {
    // Login
    await page.goto(`${BASE}/front/central.php`);
    await page.fill('input[name="name"]', USER);
    await page.fill('input[name="pass"]', PASS);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);

    // Go to template edit page
    await page.goto(
      `${BASE}/plugins/deliveryterms/front/config.form.php?id=${TEMPLATE_ID}&update=1`
    );

    // Ensure Preview button exists
    const previewBtn = page.locator('button:has-text("Preview")');
    await expect(previewBtn).toBeVisible();

    // Toggle TipTap PoC editor (if present) and insert a header to exercise the editor path
    const toggle = page.locator('#tiptap_poc_toggle');
    if ((await toggle.count()) > 0) {
      await toggle.check();
      // Wait for the editor element
      await page.waitForSelector('#tiptap-poc-editor', { state: 'visible', timeout: 5000 });
      // Click Insert Header
      const insertBtn = page.locator('button:has-text("Insert Header")');
      if ((await insertBtn.count()) > 0) {
        await insertBtn.click();
      }
    }

    // Wait for the preview.php response and click preview
    const [response] = await Promise.all([
      page.waitForResponse(
        (resp) =>
          resp.url().includes('/plugins/deliveryterms/front/preview.php') && resp.status() === 200
      ),
      previewBtn.click(),
    ]);

    // Assert content-type is PDF
    const ct = response.headers()['content-type'] || '';
    expect(ct).toContain('application/pdf');

    // Wait for modal iframe to appear and verify blob URL
    await page.waitForSelector('#deliverytermsPreviewModal iframe', { state: 'visible' });
    const iframeSrc = await page.getAttribute('#deliverytermsPreviewModal iframe', 'src');
    expect(iframeSrc).toMatch(/^blob:/);

    // Close modal
    const closeBtn = page.locator('#deliverytermsPreviewModal button.btn-close');
    if ((await closeBtn.count()) > 0) {
      await closeBtn.click();
    }
  });
});
