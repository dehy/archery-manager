import { accounts, expect, test, ui } from '../support/fixtures';
import type { Page } from '@playwright/test';

test.use({ storageState: accounts.member.state });

// Ground (page background) of each look, as the browser computes it (assets/styles/next/_tokens.scss)
const LIGHT_GROUND = 'rgb(251, 243, 228)';
const DARK_GROUND = 'rgb(23, 18, 15)';

// Next-event card of the dashboard: ink in Doux, orange in Coloré (dark Doux: sand of the dark set)
const HERO = {
    soft: 'rgb(35, 27, 22)',
    colorful: 'rgb(201, 58, 12)',
    'soft-dark': 'rgb(44, 35, 29)',
    'colorful-dark': 'rgb(201, 58, 12)',
} as const;

async function chooseLook(page: Page, theme: 'Doux' | 'Coloré', mode: 'Système' | 'Clair' | 'Sombre'): Promise<void> {
    await page.goto('/my-account/appearance');
    await page.getByRole('radio', { name: new RegExp(`^${theme}`) }).check();
    await page.getByRole('radio', { name: mode }).check();
    await page.getByRole('button', { name: 'Enregistrer' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Apparence enregistrée' })).toBeVisible();
}

async function background(page: Page, selector: string): Promise<string> {
    return page.locator(selector).first().evaluate((element) => getComputedStyle(element).backgroundColor);
}

test.describe('appearance of the new interface', () => {
    test.skip(ui !== 'next', 'Themes: redesigned interface only');

    // The member account is shared by the whole suite: give it back its default look
    test.afterEach(async ({ page }) => {
        await chooseLook(page, 'Doux', 'Système');
    });

    for (const [theme, mode, look] of [
        ['Doux', 'Clair', 'soft'],
        ['Coloré', 'Clair', 'colorful'],
        ['Doux', 'Sombre', 'soft-dark'],
        ['Coloré', 'Sombre', 'colorful-dark'],
    ] as const) {
        test(`the ${look} look themes the dashboard and the event page`, async ({ page }) => {
            await chooseLook(page, theme, mode);

            await page.goto('/');
            const ground = look.endsWith('-dark') ? DARK_GROUND : LIGHT_GROUND;
            expect(await background(page, 'body')).toBe(ground);
            expect(await background(page, '[aria-labelledby="am-next-title"]')).toBe(HERO[look]);
            await expect(page.getByRole('heading', { name: 'À venir' })).toBeVisible();

            await page.getByRole('link', { name: /^Entraînement/ }).first().click();
            await expect(page.getByRole('region', { name: 'Ma réponse' })).toBeVisible();
            expect(await background(page, 'body')).toBe(ground);
        });
    }

    test('"Système" follows the dark mode of the device, without any choice to save', async ({ page }) => {
        await chooseLook(page, 'Doux', 'Système');

        await page.emulateMedia({ colorScheme: 'dark' });
        await page.goto('/');
        expect(await background(page, 'body')).toBe(DARK_GROUND);

        await page.emulateMedia({ colorScheme: 'light' });
        expect(await background(page, 'body')).toBe(LIGHT_GROUND);
    });

    test('the page previews a choice before it is saved', async ({ page }) => {
        await page.goto('/my-account/appearance');
        await page.getByRole('radio', { name: 'Sombre' }).check();

        await expect(page.locator('html')).toHaveAttribute('data-am-mode', 'dark');
        expect(await background(page, 'body')).toBe(DARK_GROUND);
        await expect(page.getByText('Le mode sombre reste actif')).toBeVisible();

        // Not saved: leaving the page brings the stored look back
        await page.goto('/');
        await expect(page.locator('html')).toHaveAttribute('data-am-mode', 'system');
    });
});
