import { accounts, expect, login, test as setup, ui } from '../support/fixtures';

// One saved session per role, reused by every test of that role (`test.use({ storageState })`).
// For a run of the redesigned interface (E2E_UI=next), each account opts in from Mon compte first.
for (const [role, account] of Object.entries(accounts)) {
    setup(`log in as ${role}`, async ({ page }) => {
        await login(page, account.email, account.password);
        await expect(page).not.toHaveURL(/\/login/);

        await page.goto('/my-account');
        const wanted = ui === 'next' ? 'Essayer la nouvelle interface' : "Revenir à l'interface actuelle";
        const toggle = page.getByRole('button', { name: wanted });
        if (await toggle.isVisible()) {
            await toggle.click();
            await expect(page.getByRole('alert').filter({ hasText: 'interface' })).toBeVisible();
        }

        await page.context().storageState({ path: account.state });
    });
}
