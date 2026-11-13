import { accounts, expect, login, pageTitle, test } from '../support/fixtures';
import { latestEmailTo, linkIn } from '../support/mailpit';

test.describe('sign-in', () => {
    test('a wrong password keeps the visitor on the login page with an error', async ({ page }) => {
        await login(page, accounts.member.email, 'not-the-password');

        await expect(page).toHaveURL(/\/login/);
        await expect(page.getByRole('alert').filter({ hasText: 'Identifiants invalides.' })).toBeVisible();
    });

    test('a member logs in and out', async ({ page }) => {
        // A session of its own: logging out must not end the session the other tests reuse
        await login(page, accounts.member.email, accounts.member.password);
        await expect(pageTitle(page)).toContainText('Bonjour');

        await page.getByRole('button', { name: /Saison \d{4}/ }).click();
        await page.getByRole('link', { name: 'Se déconnecter' }).click();

        await expect(page).toHaveURL(/\/login/);
        await page.goto('/');
        await expect(page).toHaveURL(/\/login/);
    });

    test('a member resets a forgotten password from the e-mail link', async ({ page, request }) => {
        // coach@ladg.com is not used by any other test, so changing its password is harmless
        const email = 'coach@ladg.com';
        const newPassword = `Nouveau-${Date.now()}-mdp!`;

        await page.goto('/login');
        await page.getByRole('link', { name: 'Mot de passe oublié ?' }).click();
        await page.getByLabel('Email').fill(email);
        await page.getByRole('button', { name: 'Envoyer un e-mail de réinitialisation du mot de passe' }).click();
        await expect(page).toHaveURL(/\/reset-password\/check-email/);

        const message = await latestEmailTo(request, email);
        await page.goto(linkIn(message.html, '/reset-password/reset/'));

        const passwords = page.getByLabel(/mot de passe/i);
        await passwords.first().fill(newPassword);
        if ((await passwords.count()) > 1) {
            await passwords.nth(1).fill(newPassword);
        }
        await page.getByRole('button', { name: /réinitialiser|enregistrer|valider/i }).click();

        await login(page, email, newPassword);
        await expect(page).not.toHaveURL(/\/login/);
    });
});
