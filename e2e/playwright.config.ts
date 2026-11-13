import { defineConfig, devices } from '@playwright/test';

/**
 * Browser tests for the web app. They run against today's screens on purpose: locators go through roles,
 * labels and visible text only (never CSS classes), so the same tests keep passing through the redesign.
 * Run them with docker-compose.e2e.yml (see e2e/README.md).
 */
export default defineConfig({
    testDir: './tests',
    // The fixtures are loaded once per run: tests that write data create their own, uniquely named
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    timeout: 30_000,
    expect: { timeout: 5_000 },
    reporter: process.env.CI
        ? [['github'], ['list'], ['html', { open: 'never' }]]
        : [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: process.env.BASE_URL ?? 'http://localhost:8080',
        locale: 'fr-FR',
        timezoneId: 'Europe/Paris',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
            testIgnore: /mobile\.spec\.ts/,
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'], viewport: { width: 390, height: 844 } },
            dependencies: ['setup'],
            testMatch: /mobile\.spec\.ts/,
        },
    ],
});
