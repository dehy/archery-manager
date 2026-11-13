import { accounts, choose, expect, handleCookieConsent, login, test, unique } from '../support/fixtures';
import { latestEmailTo, linkIn } from '../support/mailpit';

test.describe('a newcomer', () => {
    const lastname = unique('Testeur').replace(' ', '');
    const email = `${lastname.toLowerCase()}@e2e.test`;
    const password = 'Un-mot-de-passe-solide-2027!';

    test('creates an account and confirms the e-mail', async ({ page, request }) => {
        await page.goto('/login');
        await page.getByRole('link', { name: 'Créer mon compte' }).click();

        await choose(page, 'Femme');
        await page.getByLabel('Prénom *', { exact: true }).fill('Camille');
        await page.getByLabel('Nom *', { exact: true }).fill(lastname);
        await page.getByLabel('Date de naissance *').fill('1990-05-12');
        await page.getByLabel('Email *').fill(email);
        await page.getByLabel('Mot de passe *').fill(password);
        await page.getByRole('checkbox', { name: /conditions générales/ }).check();
        await page.getByRole('button', { name: 'Créer mon compte' }).click();

        const message = await latestEmailTo(request, email);
        await page.goto(linkIn(message.html, '/verify/email'));

        await login(page, email, password);
        await expect(page.getByText("Vous n'avez aucune licence.")).toBeVisible();
    });

    // Known gap: registration creates a user but no licensee, and a membership request needs a licensee
    // ("Vous devez être un licencié pour faire une demande d'adhésion."), so a newcomer can't apply on their own.
    test.fixme('applies to a club right after registering', async ({ page }) => {
        await login(page, email, password);
        await page.getByRole('link', { name: "Demande d'adhésion" }).first().click();
        await page.getByLabel('Club *').selectOption({ label: 'Les Archers de Guyenne' });
    });
});

// Fixture applicants: applicant1 has a pending request, applicant2's request is already accepted
test.describe.serial('from a membership request to an active licence', () => {
    test.use({ storageState: accounts.clubAdmin.state });
    const fftaCode = `9${String(Date.now()).slice(-6)}E`;

    test('the club admin accepts a pending request', async ({ page }) => {
        await page.goto('/club-application/manage');
        await page.getByRole('row').filter({ hasText: 'applicant1@ladg.com' }).getByRole('link', { name: 'Valider' }).click();
        await page.getByRole('button', { name: 'Accepter la demande' }).click();

        await expect(page.getByText(/La demande de .+ a été validée\./)).toBeVisible();
        await page.getByRole('tab', { name: /^Acceptées/ }).or(page.getByRole('link', { name: /^Acceptées/ })).click();
        await expect(page.getByRole('row').filter({ hasText: 'applicant1@ladg.com' })).toContainText('activation FFTA requise');
    });

    test('the club admin activates an accepted request with its FFTA code', async ({ page }) => {
        await page.goto('/club-application/manage');
        await page.getByRole('tab', { name: /^Acceptées/ }).or(page.getByRole('link', { name: /^Acceptées/ })).click();
        await page.getByRole('row').filter({ hasText: 'applicant2@ladg.com' }).getByRole('link', { name: /activation FFTA requise|Activer/ }).click();
        await page.getByLabel('Code licence FFTA').fill(fftaCode);
        await page.getByRole('button', { name: 'Vérifier le code FFTA' }).click();
        await expect(page.getByText(`Code ${fftaCode} disponible`)).toBeVisible();
        await page.getByRole('checkbox').first().check();
        await page.getByRole('button', { name: 'Activer la licence' }).click();

        await expect(page.getByText('La licence FFTA a été activée pour cette demande.')).toBeVisible();
    });

    test('the new member now reaches the calendar', async ({ browser }) => {
        const context = await browser.newContext();
        const page = await context.newPage();
        await handleCookieConsent(page);
        await login(page, 'applicant2@ladg.com', 'user');
        await page.getByRole('navigation').getByRole('link', { name: 'Calendrier' }).first().click();

        await expect(page.getByRole('heading', { level: 2, name: 'Calendrier' })).toBeVisible();
        await context.close();
    });
});
