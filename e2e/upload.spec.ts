import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

/**
 * Uploading a Shiplog file in a real browser. This WRITES to the site's database: it uploads the
 * README example (tests/Fixtures/shiplog-example.json), which adds or replaces its 2 cards and adds
 * one row to the import history. The broken copy writes nothing. Needs the first administrator:
 *   PLAYWRIGHT_ADMIN_EMAIL=... PLAYWRIGHT_ADMIN_PASSWORD=... bin/playwright.sh <path>
 * Screenshots land in test-results/ (git-ignored).
 */
const example = 'tests/Fixtures/shiplog-example.json';

// One sign-in for the whole flow: Fortify allows 5 sign-in attempts a minute (FortifyServiceProvider).
test('an upload lists every problem in a broken file, and imports a good one into the history', async ({ page }) => {
    const email = process.env.PLAYWRIGHT_ADMIN_EMAIL;
    const password = process.env.PLAYWRIGHT_ADMIN_PASSWORD;
    test.skip(!email || !password, 'Set PLAYWRIGHT_ADMIN_EMAIL and PLAYWRIGHT_ADMIN_PASSWORD to run this case.');

    await page.setViewportSize({ width: 1366, height: 900 });
    await page.goto('/login');
    await page.getByLabel('Email').fill(email!);
    await page.getByLabel('Password').fill(password!);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).toHaveURL(/\/$/);

    const broken = JSON.parse(readFileSync(example, 'utf8'));
    broken.lanes[0].color = 'blue';
    broken.entries[1].summary = '';
    await page.goto('/imports/create');
    await page.getByLabel('File').setInputFiles({ name: 'broken.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify(broken)) });
    await page.getByRole('button', { name: 'Upload' }).click();
    await expect(page).toHaveURL(/\/imports\/create$/);
    await expect(page.getByText('The file was refused: 2 problems.')).toBeVisible();
    await expect(page.getByRole('alert')).toContainText('lanes[0] color "blue" is not written #rrggbb.');
    await expect(page.getByRole('alert')).toContainText('entries[1] 2026-10-21 lane "side-project" summary is empty.');
    await page.screenshot({ path: 'test-results/upload-refused.png', fullPage: true });

    await page.getByLabel('File').setInputFiles(example);
    await page.getByRole('button', { name: 'Upload' }).click();
    await expect(page).toHaveURL(/\/imports$/);
    await expect(page.locator('#toast-container')).toContainText('Imported shiplog-example.json:');
    await expect(page.locator('tbody tr').first()).toContainText('shiplog-example.json');
    await page.screenshot({ path: 'test-results/upload-history-desktop.png', fullPage: true });

    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
    await page.screenshot({ path: 'test-results/upload-history-phone.png', fullPage: true });
});
