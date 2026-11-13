import { accounts, expect, test } from '../support/fixtures';

test.use({ storageState: accounts.member.state });

test('the mobile tab bar reaches the calendar, the club and the "other" menu', async ({ page }) => {
    await page.goto('/');
    const tabs = page.getByRole('navigation', { name: 'Onglets' });

    await tabs.getByRole('link', { name: 'Calendrier' }).click();
    await expect(page).toHaveURL(/\/events/);
    await expect(page.getByRole('heading', { level: 2, name: 'Calendrier' })).toBeVisible();

    await tabs.getByRole('link', { name: 'Mon Club' }).click();
    await expect(page).toHaveURL(/\/my-club/);

    // Last tab: the archer's first name, opening the "other" menu (account, profile, log out)
    await tabs.getByRole('listitem').last().getByRole('link').click();
    await expect(page.getByRole('link', { name: 'Se déconnecter' })).toBeVisible();
});
