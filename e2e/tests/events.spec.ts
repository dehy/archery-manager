import { accounts, choose, expect, pageTitle, test } from '../support/fixtures';

test.use({ storageState: accounts.member.state });

test.describe('events for a member', () => {
    test('the dashboard lists the next events', async ({ page }) => {
        await page.goto('/');

        await expect(page.getByRole('heading', { name: 'Prochains événements' })).toBeVisible();
        await expect(page.getByRole('link', { name: /^Concours/ }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: /^Entraînement/ }).first()).toBeVisible();
    });

    test('a member answers a contest: state, target face and start', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: /^Concours salle/ }).first().click();
        await expect(pageTitle(page)).toContainText('Concours');

        const answer = page.getByRole('button', { name: /^(S'inscrire|Inscrit•e|Absent•e)$/ });
        await answer.click();
        const dialog = page.getByRole('dialog', { name: 'Ma participation' });
        await choose(dialog, 'Je souhaite y aller');
        await expect(dialog.getByLabel('Activité')).toBeVisible();
        await dialog.getByLabel('Type de cible').selectOption({ label: 'Trispot' });
        await dialog.getByLabel('Départ').selectOption({ label: 'n°1' });
        await dialog.getByRole('button', { name: 'Enregistrer ma participation' }).click();

        await expect(dialog).toBeHidden();
        // "Interested" keeps the "S'inscrire" label on today's page: the saved choice shows in the form
        await page.reload();
        await answer.click();
        await expect(page.getByRole('dialog', { name: 'Ma participation' }).getByRole('radio', { name: 'Je souhaite y aller' })).toBeChecked();
    });

    test('a member says they will miss a training, then comes back', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: /^Entraînement Compétiteurs/ }).first().click();

        await page.getByRole('button', { name: /^(Participe|Absent•e|Définir participation)$/ }).click();
        let dialog = page.getByRole('dialog', { name: 'Ma participation' });
        await choose(dialog, "Je n'y vais pas");
        await dialog.getByRole('button', { name: 'Enregistrer', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Absent•e' })).toBeVisible();

        await page.getByRole('button', { name: 'Absent•e' }).click();
        dialog = page.getByRole('dialog', { name: 'Ma participation' });
        await choose(dialog, 'Je suis inscrit');
        await dialog.getByRole('button', { name: 'Enregistrer', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Participe' })).toBeVisible();
    });

    test("an event for other groups can't be answered, and says why", async ({ page }) => {
        await page.goto('/events');
        // The member is in Groupe Compétiteurs only; Loisir trainings are reserved to Groupe Loisir
        await page.getByRole('link', { name: 'Loisir', exact: true }).first().click();

        const answer = page.getByRole('button', { name: 'Définir participation' });
        await expect(answer).toBeDisabled();
        await expect(answer).toHaveAccessibleDescription("Cet entraînement est réservé à d'autres groupes");
        await expect(page.getByText("Cet entraînement est réservé à d'autres groupes")).toBeVisible();
    });

    test('the calendar moves between months', async ({ page }) => {
        await page.goto('/events');
        const month = page.getByRole('heading', { level: 3 });
        const current = (await month.textContent())!.trim();

        await page.getByRole('link', { name: 'Mois suivant' }).click();
        await expect(month).not.toHaveText(current);
        await page.getByRole('link', { name: 'Mois précédent' }).click();
        await expect(month).toHaveText(current);
        await page.getByRole('link', { name: 'Mois suivant' }).click();
        await page.getByRole('link', { name: "Aujourd'hui" }).click();
        await expect(month).toHaveText(current);
    });
});
