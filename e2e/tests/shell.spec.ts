import { accounts, expect, test, ui } from '../support/fixtures';

// The redesigned shell (templates/base_next.html.twig): only meaningful in the E2E_UI=next run
test.skip(ui !== 'next', 'Redesigned interface only (E2E_UI=next)');

test.describe('member', () => {
    test.use({ storageState: accounts.member.state });

    test('the sidebar reaches every member area and marks the current one', async ({ page }) => {
        await page.goto('/');
        const nav = page.getByRole('navigation', { name: 'Navigation principale' });

        await expect(nav.getByRole('link', { name: 'Accueil' })).toHaveAttribute('aria-current', 'page');
        await expect(nav.getByText('Gestion')).toHaveCount(0);

        for (const [label, url] of [['Calendrier', /\/events/], ['Annuaire', /\/licensees/], ['Le club', /\/my-club/], ['Moi', /\/my-profile/]] as const) {
            await nav.getByRole('link', { name: label }).click();
            await expect(page).toHaveURL(url);
            await expect(nav.getByRole('link', { name: label })).toHaveAttribute('aria-current', 'page');
        }
    });

    test('the account block opens the account menu', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('button', { name: /Saison \d{4}/ }).click();

        await expect(page.getByText("Changer d'archer")).toBeVisible();
        await expect(page.getByRole('link', { name: 'Mon compte' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Se déconnecter' })).toBeVisible();
    });
});

test.describe('club admin', () => {
    test.use({ storageState: accounts.clubAdmin.state });

    test('the sidebar shows the management area', async ({ page }) => {
        await page.goto('/');
        const nav = page.getByRole('navigation', { name: 'Navigation principale' });

        await nav.getByRole('link', { name: 'Événements' }).click();
        await expect(page).toHaveURL(/\/events\/manage/);
        await nav.getByRole('link', { name: /^Demandes/ }).click();
        await expect(page).toHaveURL(/\/club-application\/manage/);
    });
});
