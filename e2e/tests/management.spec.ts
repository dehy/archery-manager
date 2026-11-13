import { accounts, expect, test, unique } from '../support/fixtures';

test.use({ storageState: accounts.clubAdmin.state });

/** A date-time-local value `days` days from now, at `hour`:00 */
function inDays(days: number, hour: number): string {
    const date = new Date();
    date.setDate(date.getDate() + days);
    date.setHours(hour, 0, 0, 0);
    const pad = (value: number) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(hour)}:00`;
}

test.describe('club management', () => {
    test('a club admin creates a training for one group, then deletes it', async ({ page }) => {
        // The list shows training names without their "Entraînement" prefix: find the event by a unique tag
        const tag = unique('E2E');
        const name = `Séance ${tag}`;

        await page.goto('/events/manage');
        await page.getByRole('link', { name: 'Créer un événement' }).click();
        await page.getByRole('link', { name: 'Créer un entraînement', exact: true }).click();

        await page.getByLabel("Nom de l'événement").fill(name);
        // Far ahead: the list is paginated newest first, so the event shows on its first page
        await page.getByLabel('Date et heure de début').fill(inDays(400, 18));
        await page.getByLabel('Date et heure de fin').fill(inDays(400, 20));
        await page.getByLabel('Adresse').fill('Gymnase Ginko, Bordeaux');
        await page.getByRole('checkbox', { name: 'Groupe Loisir' }).check();
        await page.getByRole('button', { name: 'Enregistrer' }).click();

        await page.goto('/events/manage');
        const row = page.getByRole('row').filter({ hasText: tag });
        await expect(row).toContainText('Groupe Loisir');

        await row.getByRole('button', { name: 'Supprimer' }).click();
        await page.getByRole('dialog', { name: 'Confirmer la suppression' }).getByRole('button', { name: 'Supprimer' }).click();
        await expect(page.getByRole('row').filter({ hasText: tag })).toHaveCount(0);
    });

    test('a club admin imports the FFTA licensee CSV', async ({ page }) => {
        const code = `9${String(Date.now()).slice(-6)}C`;
        const lastname = unique('Importe').replace(' ', '');
        const csv =
            '﻿"Code Adhérent";"Nom";"Prénom";"Sexe";"Date de naissance";"État";"Valide depuis le";"Type";"Catégorie âge";"Email"\n' +
            `"${code}";"${lastname}";"Alex";"Masculin";"12/05/1985";"Active";"01/09/2026";"Adulte pratique en club";"Sénior 1";"${lastname.toLowerCase()}@e2e.test"\n`;

        await page.goto('/licensees/manage/import');
        await page.getByLabel('Export CSV des licenciés FFTA').setInputFiles({ name: 'licencies.csv', mimeType: 'text/csv', buffer: Buffer.from(csv) });
        await page.getByRole('button', { name: 'Analyser le fichier' }).click();

        await expect(page.getByRole('heading', { name: 'Vérifier l’import FFTA' })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: code })).toBeVisible();
        await page.getByRole('button', { name: 'Confirmer l’import' }).click();

        await expect(page).toHaveURL(/\/licensees$/);
        await expect(page.getByRole('heading', { name: new RegExp(`Alex ${lastname}`) })).toBeVisible();
    });

    test('a CSV with missing columns is refused before any change', async ({ page }) => {
        await page.goto('/licensees/manage/import');
        await page.getByLabel('Export CSV des licenciés FFTA').setInputFiles({
            name: 'incomplet.csv',
            mimeType: 'text/csv',
            buffer: Buffer.from('"Code Adhérent";"Nom"\n"9999999Z";"Personne"\n'),
        });
        await page.getByRole('button', { name: 'Analyser le fichier' }).click();

        await expect(page.getByText(/Colonnes CSV manquantes/)).toBeVisible();
        await expect(page).toHaveURL(/\/licensees\/manage\/import$/);
    });

    test('a club admin lends equipment and records its return', async ({ page }) => {
        const name = unique('Arc E2E');

        await page.goto('/club-equipment');
        await page.getByRole('link', { name: 'Ajouter du matériel' }).click();
        await page.getByLabel("Type d'équipement").selectOption({ index: 1 });
        await page.getByLabel('Nom / Identification').fill(name);
        await page.getByLabel('Quantité en stock').fill('2');
        await page.getByRole('button', { name: 'Enregistrer' }).click();

        await page.goto('/club-equipment');
        await page.getByRole('row').filter({ hasText: name }).getByRole('link', { name: /Prêter/ }).click();
        await page.getByLabel('Emprunteur').selectOption({ index: 1 });
        await page.getByRole('button', { name: 'Confirmer le prêt' }).click();

        await page.goto('/club-equipment/loans');
        const loan = page.getByRole('row').filter({ hasText: name });
        await expect(loan).toBeVisible();
        page.once('dialog', (dialog) => dialog.accept());
        await loan.getByRole('button', { name: 'Retourné' }).click();
        // A returned loan stays in the history, without the return action
        await expect(page.getByRole('row').filter({ hasText: name }).getByRole('button', { name: 'Retourné' })).toHaveCount(0);
    });
});
