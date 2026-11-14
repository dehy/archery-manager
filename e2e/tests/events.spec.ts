import { accounts, choose, expect, pageTitle, test, ui } from '../support/fixtures';

test.use({ storageState: accounts.member.state });

test.describe('events for a member', () => {
    test('the dashboard lists the next events', async ({ page }) => {
        await page.goto('/');

        await expect(page.getByRole('heading', { name: ui === 'next' ? 'À venir' : 'Prochains événements' })).toBeVisible();
        await expect(page.getByRole('link', { name: /^Concours/ }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: /^Entraînement/ }).first()).toBeVisible();
    });

    test('from the dashboard, a member misses a training in one tap, then comes back', async ({ page }) => {
        test.skip(ui !== 'next', 'One-tap answer: redesigned dashboard only');
        await page.goto('/');

        const absent = page.getByRole('button', { name: /^Je serai absent·e : / }).first();
        const name = (await absent.getAttribute('aria-label'))!.replace(/^Je serai absent·e : /, '');
        await absent.click();
        await expect(page.getByRole('alert').filter({ hasText: "C'est noté : absent·e" })).toBeVisible();

        await page.getByRole('button', { name: `Je viens finalement : ${name}` }).click();
        await expect(page.getByRole('alert').filter({ hasText: "C'est noté : présent·e" })).toBeVisible();
        await expect(page.getByRole('button', { name: `Je serai absent·e : ${name}` })).toBeVisible();
    });

    test('a member answers a contest: state, target face and start', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: /^Concours salle/ }).first().click();
        await expect(pageTitle(page)).toContainText('Concours');

        if (ui === 'next') {
            // The answer is a form on the page itself, no dialog
            const form = page.getByRole('region', { name: 'Ma réponse' });
            await choose(form, 'Je suis inscrit·e');
            await expect(form.getByLabel('Activité')).toBeVisible();
            await form.getByLabel('Blason').selectOption({ label: 'Trispot' });
            await form.getByLabel('Départ').selectOption({ label: 'n°1' });
            await form.getByRole('button', { name: 'Enregistrer' }).click();

            await expect(page.getByRole('alert').filter({ hasText: 'Votre réponse est enregistrée' })).toBeVisible();
            await expect(form.getByRole('radio', { name: 'Je suis inscrit·e' })).toBeChecked();
            await expect(form.getByLabel('Départ')).toHaveValue('1');

            // "Je n'y vais pas": the target face and the start don't apply
            await choose(form, "Je n'y vais pas");
            await expect(form.getByLabel('Blason')).toBeHidden();
            return;
        }

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

        if (ui === 'next') {
            const form = page.getByRole('region', { name: 'Ma réponse' });
            await choose(form, 'Absent·e');
            await form.getByRole('button', { name: 'Enregistrer' }).click();
            await expect(page.getByRole('alert').filter({ hasText: 'Votre réponse est enregistrée' })).toBeVisible();
            await expect(form.getByRole('radio', { name: 'Absent·e' })).toBeChecked();

            await choose(form, 'Présent·e');
            await form.getByRole('button', { name: 'Enregistrer' }).click();
            await expect(form.getByRole('radio', { name: 'Présent·e' })).toBeChecked();
            return;
        }

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
        // Today's calendar shows the event name, the redesign its title with the answer before it
        await page.getByRole('link', ui === 'next' ? { name: /Entraînement Loisir$/ } : { name: 'Loisir', exact: true }).first().click();

        if (ui === 'next') {
            const form = page.getByRole('region', { name: 'Ma réponse' });
            await expect(form.getByText(/Réservé au groupe .*Loisir/)).toBeVisible();
            await expect(form.getByRole('button', { name: 'Enregistrer' })).toHaveCount(0);
            return;
        }

        const answer = page.getByRole('button', { name: 'Définir participation' });
        await expect(answer).toBeDisabled();
        await expect(answer).toHaveAccessibleDescription("Cet entraînement est réservé à d'autres groupes");
        await expect(page.getByText("Cet entraînement est réservé à d'autres groupes")).toBeVisible();
    });

    test('the calendar moves between months', async ({ page }) => {
        await page.goto('/events');
        // The redesign titles the page with the month itself
        const month = ui === 'next' ? pageTitle(page) : page.getByRole('heading', { level: 3 });
        const current = (await month.textContent())!.trim();

        await page.getByRole('link', { name: 'Mois suivant' }).click();
        await expect(month).not.toHaveText(current);
        await page.getByRole('link', { name: 'Mois précédent' }).click();
        await expect(month).toHaveText(current);
        await page.getByRole('link', { name: 'Mois suivant' }).click();
        await page.getByRole('link', { name: "Aujourd'hui" }).click();
        await expect(month).toHaveText(current);
    });

    test('from the calendar list, a member misses a training in one tap and stays on the calendar', async ({ page }) => {
        test.skip(ui !== 'next', 'Calendar list with one-tap answers: redesigned calendar only');
        await page.goto('/events');
        await page.getByRole('link', { name: 'Liste' }).click();
        await expect(page.getByRole('link', { name: 'Liste' })).toHaveAttribute('aria-current', 'page');

        const absent = page.getByRole('button', { name: /^Je serai absent·e : / }).first();
        const name = (await absent.getAttribute('aria-label'))!.replace(/^Je serai absent·e : /, '');
        await absent.click();
        await expect(page.getByRole('alert').filter({ hasText: "C'est noté : absent·e" })).toBeVisible();
        await expect(page).toHaveURL(/\/events\?.*view=list/);

        await page.getByRole('button', { name: `Je viens finalement : ${name}` }).click();
        await expect(page.getByRole('button', { name: `Je serai absent·e : ${name}` })).toBeVisible();
    });
});
