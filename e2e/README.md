# Browser tests (Playwright)

End-to-end tests of the web app in a real browser (Chromium), run on every pull request by `.github/workflows/e2e.yml`.

## Run them

```bash
docker compose -p archery_e2e -f docker-compose.e2e.yml up --build --abort-on-container-exit --exit-code-from playwright
docker compose -p archery_e2e -f docker-compose.e2e.yml down -v
```

The stack (`docker-compose.e2e.yml`):

- **app**: the Docker `dev` image started with `command: e2e` and `APP_ENV=e2e`. The `e2e` entrypoint builds the
  production assets, migrates, loads the fixtures **once**, then serves the app. It answers as `http://archery-manager`
  (never `app`: Chromium treats a bare `app` host as the HSTS-preloaded `.app` top-level domain and forces HTTPS).
- **database** (`app_e2e`), **redis**, **mailpit** (catches every e-mail; tests read them through its API).
- **playwright**: the official image, pinned to the same version as `@playwright/test` in `package.json`.

The `e2e` Symfony environment (`when@e2e` blocks in `config/`, `.env.e2e`) is production-like: no debug, real sessions,
production security headers, no captcha, no rate limits, local file storage, e-mails to Mailpit.

Report, traces and screenshots land in `e2e/playwright-report/` and `e2e/test-results/` (open the report with
`npx playwright show-report`, a trace with `npx playwright show-trace <trace.zip>`).

While writing tests, keep the stack up and rerun only the runner:

```bash
docker compose -p archery_e2e -f docker-compose.e2e.yml up -d --build app
docker compose -p archery_e2e -f docker-compose.e2e.yml run --rm --no-deps playwright \
  sh -c "npm ci && npx playwright test tests/events.spec.ts"
```

Some tests change the fixtures (accepting a membership request, for example): restart the `app` service
(`up -d --force-recreate app`) to reload them before a full rerun.

## Write them

- **Locators by role, label or visible text only** (`getByRole`, `getByLabel`, `getByText`), never CSS classes or ids:
  the same tests must keep passing through the redesign. When an element can't be reached that way, it usually has an
  accessibility problem: fix the template (name the control, label the field, mark the region).
- **Data:** the fixtures are loaded once per run. A test that writes creates its own data with `unique()`; it never
  depends on Faker names (read them from the page instead).
- **Sessions:** `tests/auth.setup.ts` saves one login per role (`support/fixtures.ts` → `accounts`). Use
  `test.use({ storageState: accounts.member.state })`. A test that logs out or switches archer logs in on its own,
  so it doesn't end the shared session.
- **Helpers** (`support/`): `pageTitle()` (today's layout has two level-1 headings), `choose()` (radios styled as
  buttons: click the label), `latestEmailTo()` / `linkIn()` (Mailpit), `solidPng()` (a valid test photo). The cookie
  consent box is answered automatically.
- **Projects:** `desktop` (Desktop Chrome) and `mobile` (390 × 844, only `*mobile.spec.ts`).
- `tests/accessibility.spec.ts` runs axe (WCAG 2.2 AA) on the key screens in **report-only** mode: results are
  attached to the report and never fail the run, until the redesign makes it a gate.

## Known gaps found by these tests

- `membership.spec.ts` › *applies to a club right after registering* is `fixme`: registration creates a user without a
  licensee, and a membership request requires one, so a newcomer can't apply on their own (#165).
