import { test as base, expect, type Locator, type Page } from '@playwright/test';

/** Accounts from the fixtures (fixtures/*.yml). Every fixture password below is `user`, except the admin's. */
export const accounts = {
    /** Club admin and coach of Les Archers de Bordeaux Guyenne */
    clubAdmin: { email: 'clubadmin@ladg.com', password: 'user', state: '.auth/club-admin.json' },
    /** Plain member; their account holds two licensees */
    member: { email: 'user1@ladg.com', password: 'user', state: '.auth/member.json' },
    /** Account without a licence, already applied to the club */
    applicant: { email: 'applicant1@ladg.com', password: 'user', state: '.auth/applicant.json' },
} as const;

/** A suffix that keeps the data a test creates apart from the fixtures and from earlier runs */
export function unique(prefix: string): string {
    return `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1296).toString(36)}`;
}

/** Answers the cookie consent box (refuses analytics) whenever it shows up, so it never blocks a click */
export async function handleCookieConsent(page: Page): Promise<void> {
    await page.addLocatorHandler(page.getByRole('button', { name: 'Refuser', exact: true }), async (button) => {
        await button.click();
    });
}

/**
 * The page title. Today's layout also renders the club name as a level-1 heading in the navbar (and a second,
 * mobile-only title), so take the last visible one; the redesign keeps a single level-1 heading.
 */
export function pageTitle(page: Page): Locator {
    return page.getByRole('heading', { level: 1 }).filter({ visible: true }).last();
}

/** Picks a choice the way a person does: by clicking its visible label (today's radios are styled as buttons) */
export async function choose(scope: Page | Locator, label: string): Promise<void> {
    await scope.getByText(label, { exact: true }).click();
    await expect(scope.getByRole('radio', { name: label })).toBeChecked();
}

export async function login(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Mot de passe').fill(password);
    await page.getByRole('button', { name: 'Se connecter' }).click();
}

export const test = base.extend<{ consent: void }>({
    consent: [
        async ({ page }, use) => {
            await handleCookieConsent(page);
            await use();
        },
        { auto: true },
    ],
});

export { expect };
