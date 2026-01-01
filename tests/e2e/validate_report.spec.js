const { test, expect } = require('@playwright/test');
const path = require('path');

// Usage: npx playwright test tests/e2e/validate_report.spec.js

test.describe('Validate extracted HTML report', () => {
  test('report file loads and contains expected markers', async ({ page }) => {
    const reportPath = process.env.REPORT_PATH || path.join(process.cwd(), 'extracted_report', 'index.html');
    const url = 'file://' + reportPath;

    await page.goto(url);

    // Basic expectations: page has a title, and it contains either 'report' or 'playwright'
    const title = await page.title();
    const bodyText = await page.locator('body').innerText();

    const hasReportMarker = /report/i.test(title) || /report/i.test(bodyText) || /playwright/i.test(bodyText);
    expect(hasReportMarker).toBeTruthy();

    // Also ensure main element exists or a heading is present
    const mainCount = await page.locator('main').count();
    const headingCount = await page.locator('h1').count();
    expect(mainCount + headingCount).toBeGreaterThan(0);
  });
});