import { expect, type Page, test } from '@playwright/test';

/**
 * The board in a real browser at desktop and phone widths. Changes nothing but the browser
 * session. Needs the first administrator's details and at least one imported file (the README
 * example will do):
 *   PLAYWRIGHT_ADMIN_EMAIL=... PLAYWRIGHT_ADMIN_PASSWORD=... bin/playwright.sh <path>
 * Screenshots land in test-results/ (git-ignored).
 */
async function checkBoard(page: Page, screen: string): Promise<void> {
    await expect(page.getByRole('heading', { level: 1, name: 'Board' })).toBeVisible();

    // A card opens its details in the side pane; Escape closes it and gives focus back to the card.
    const card = page.locator('.board-card:visible').first();
    const pane = page.locator('#board-pane');
    await card.click();
    await expect(pane).toBeVisible();
    await expect(pane.locator('.board-sources li').first()).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(pane).toBeHidden();
    await expect(card).toBeFocused();

    // On a phone the cards stack: nothing may push the page sideways.
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);

    await page.screenshot({ path: `test-results/board-${screen}.png`, fullPage: true });
}

// The Lanes filter hides a lane's column, remembers it in this browser, and Select all brings it back.
async function checkLaneFilter(page: Page): Promise<void> {
    const filter = page.locator('[data-lane-filter]');
    const lane = filter.locator('[data-filter-lane]').first();
    const header = page.locator(`.board-lane[data-lane="${await lane.getAttribute('value')}"]`);

    await filter.getByRole('button').click();
    await lane.uncheck();
    await expect(header).toBeHidden();

    await page.reload();
    await expect(header).toBeHidden();

    await filter.getByRole('button').click();
    await filter.getByLabel('Select all').check();
    await expect(header).toBeVisible();
}

// One sign-in for both widths: Fortify allows 5 sign-in attempts a minute (FortifyServiceProvider).
test('the board opens a card\'s sources in the side pane and filters lanes, on a desktop and on a phone', async ({ page }) => {
    const email = process.env.PLAYWRIGHT_ADMIN_EMAIL;
    const password = process.env.PLAYWRIGHT_ADMIN_PASSWORD;
    test.skip(!email || !password, 'Set PLAYWRIGHT_ADMIN_EMAIL and PLAYWRIGHT_ADMIN_PASSWORD to run this case.');

    await page.setViewportSize({ width: 1366, height: 900 });
    await page.goto('/login');
    await page.getByLabel('Email').fill(email!);
    await page.getByLabel('Password').fill(password!);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).toHaveURL(/\/$/);
    await checkBoard(page, 'desktop');
    await checkLaneFilter(page);

    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();
    await checkBoard(page, 'phone');
});
