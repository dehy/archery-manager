import { accounts, expect, login, test as setup } from '../support/fixtures';

// One saved session per role, reused by every test of that role (`test.use({ storageState })`)
for (const [role, account] of Object.entries(accounts)) {
    setup(`log in as ${role}`, async ({ page }) => {
        await login(page, account.email, account.password);
        await expect(page).not.toHaveURL(/\/login/);
        await page.context().storageState({ path: account.state });
    });
}
