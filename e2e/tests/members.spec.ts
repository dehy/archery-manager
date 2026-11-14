import { accounts, expect, login, pageTitle, test, ui } from '../support/fixtures';
import { solidPng } from '../support/png';

/** "Firstname L." — what a plain member may see of other members */
const SHORT_NAME = /^\S.*\s\p{Lu}\.$/u;

test.describe('directory privacy', () => {
    test.describe('as a member', () => {
        test.use({ storageState: accounts.member.state });

        test('members only see "Firstname L."', async ({ page }) => {
            await page.goto('/licensees');
            const names = page.getByRole('heading', { level: ui === 'next' ? 2 : 5 }).filter({ hasNotText: /^Filtrer/ });
            await expect(names.first()).toBeVisible();

            for (const name of await names.allTextContents()) {
                expect(name.trim()).toMatch(SHORT_NAME);
            }
        });
    });

    test.describe('as a club admin', () => {
        test.use({ storageState: accounts.clubAdmin.state });

        test('club admins see full names', async ({ page }) => {
            await page.goto('/licensees');
            const names = page.getByRole('heading', { level: ui === 'next' ? 2 : 5 }).filter({ hasNotText: /^Filtrer/ });
            await expect(names.first()).toBeVisible();

            const fullNames = (await names.allTextContents()).filter((name) => !SHORT_NAME.test(name.trim()));
            expect(fullNames.length).toBeGreaterThan(0);
        });
    });
});

test.describe('profile', () => {
    test.use({ storageState: accounts.member.state });

    test('a member sees their own profile and changes their photo', async ({ page }) => {
        await page.goto('/my-profile');
        await expect(pageTitle(page)).not.toHaveText(SHORT_NAME);
        await expect(page.getByRole('heading', { name: ui === 'next' ? 'Ma licence' : /^Licence \d{4}$/ })).toBeVisible();

        // The redesign puts a camera button on the photo itself
        await page.getByRole('button', { name: ui === 'next' ? 'Changer la photo de profil' : 'Modifier la photo' }).click();
        const dialog = page.getByRole('dialog', { name: 'Photo de profil' });
        await dialog.getByLabel('Nouvelle photo').setInputFiles({ name: 'photo.png', mimeType: 'image/png', buffer: solidPng() });
        await dialog.getByRole('button', { name: 'Enregistrer' }).click();

        await expect(page.getByText('Photo de profil mise à jour.')).toBeVisible();
    });
});

test.describe('several archers on one account', () => {
    test('a member switches to their other archer', async ({ page }) => {
        // A session of its own: the archer choice is kept in the server session the other tests reuse
        await login(page, accounts.member.email, accounts.member.password);
        await page.goto('/my-account');

        if (ui === 'next') {
            // The redesigned account lists the archers with a "Passer à …" button for the others
            await expect(page.getByRole('heading', { name: 'Archers du compte' })).toBeVisible();
            const switchTo = page.getByRole('link', { name: /^Passer à / }).first();
            const fullName = (await switchTo.textContent())!.replace(/^\s*Passer à\s*/, '').trim();
            await switchTo.click();
            await expect(page.getByRole('link', { name: new RegExp(`^Passer à ${fullName}`) })).toHaveCount(0);

            await page.goto('/');
            await expect(pageTitle(page)).toContainText(fullName.split(' ')[0]);
            return;
        }

        await expect(page.getByRole('heading', { name: /^Mes licenciés/ })).toBeVisible();
        // Each archer of the account is a link; the current one says "Sélectionné"
        const other = page.getByRole('link').filter({ has: page.getByRole('heading', { level: 5 }) }).filter({ hasNotText: 'Sélectionné' }).first();
        const otherName = (await other.getByRole('heading', { level: 5 }).textContent())!.trim();
        // Switching happens from the account menu, which lists the other archers of the account
        await page.getByRole('button', { name: /Saison \d{4}/ }).click();
        await page.getByRole('link', { name: otherName, exact: true }).click();

        await page.goto('/');
        const firstname = otherName.split(' ')[0];
        await expect(pageTitle(page)).toContainText(firstname);
        await expect(page.getByRole('button', { name: new RegExp(`^${otherName.replace('.', '\\.')} Saison`) })).toBeVisible();
    });
});
