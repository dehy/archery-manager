import AxeBuilder from '@axe-core/playwright';
import { accounts, test } from '../support/fixtures';

/**
 * Accessibility scan (axe, WCAG 2.2 A and AA) of the key screens. REPORT ONLY for now: the violations are attached
 * to the run and summarised as annotations, the test never fails. Turn it into a gate once the redesign fixes them.
 */
const screens: Record<keyof typeof accounts | 'anonymous', string[]> = {
    anonymous: ['/login', '/register', '/reset-password'],
    member: ['/', '/events', '/licensees', '/my-profile', '/my-account', '/my-club'],
    clubAdmin: ['/events/manage', '/club-application/manage', '/club/members', '/club-equipment', '/licensees/manage/import'],
    applicant: ['/', '/club-application/status'],
};

for (const [role, urls] of Object.entries(screens)) {
    test.describe(`accessibility (${role})`, () => {
        if (role !== 'anonymous') {
            test.use({ storageState: accounts[role as keyof typeof accounts].state });
        }

        for (const url of urls) {
            test(`${url} (report only)`, async ({ page }, testInfo) => {
                await page.goto(url);
                const results = await new AxeBuilder({ page })
                    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
                    .analyze();

                const summary = results.violations.map((violation) => `${violation.impact ?? 'n/a'} · ${violation.id} (${violation.nodes.length})`);
                testInfo.annotations.push({
                    type: 'axe',
                    description: summary.length ? summary.join(', ') : 'no violation',
                });
                await testInfo.attach(`axe ${role} ${url}`, {
                    body: JSON.stringify(results.violations, null, 2),
                    contentType: 'application/json',
                });
            });
        }
    });
}
