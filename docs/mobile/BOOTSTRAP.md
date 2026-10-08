# Bootstrapping the Archery Manager mobile app

Instructions for a **new Claude Code session**, started in a **new, empty repository** that will hold the
React Native app. Read this file completely before writing anything. It was written at the end of the API
work of phases 0 to 2; the API repository is the source of truth for the contract.

> Copy this file into the new repository (for instance as `docs/BOOTSTRAP.md`) or paste it as the first message.
> The API repository is `dehy/archery-manager` (Symfony). Its contract is `docs/api/openapi.yaml`.

---

## 1. The project

Archery Manager is a web app for a French archery club (Les Archers de Bordeaux Guyenne). A React Native
app for **iOS and Android** is being built for its **members**, and the web app is turning into an API.

- **App scope: members only.** Club-admin features (managing events, groups, licensees, applications, equipment
  loans), EasyAdmin, audit log and console commands are **not** in the app.
- **Languages.** The UI is **French first** (i18n from the start so more languages can come). Everything
  technical is **English**: code, comments, identifiers, commit messages, error handling, logs, docs.
- **Plan.** A Notion plan "Archery Manager Mobile" has 8 phases (0 to 7), each ending with something testable on
  a device. It is a private draft (the owner may have moved it to the "Developments" teamspace): search Notion for it
  if you have the connector, otherwise use the phase list in section 6.

## 2. Decisions already made (do not reopen without a reason)

| Topic | Decision |
|---|---|
| Toolchain | **Expo (managed workflow) + EAS** (Build, Submit, Update). No bare React Native. |
| Repo | A **separate repository** from the API. The app consumes the OpenAPI document. |
| Language | TypeScript, `strict`. |
| Navigation | Expo Router. |
| Server state | TanStack Query. Light client state: Zustand. |
| Forms | react-hook-form + zod. |
| Secrets | `expo-secure-store` for tokens. Optional biometric unlock later. |
| Push | `expo-notifications` (phase 6). |
| API client | **Generated from `openapi.yaml`** (orval or openapi-typescript + a small fetch wrapper). Never hand-write types for API payloads. |
| Quality | ESLint + Prettier, `tsc --noEmit`, Jest + React Native Testing Library, Maestro for E2E, Sentry. |
| Delivery | Preview builds through EAS on every PR; TestFlight and Play internal testing from the start. |

### Naming (proposed, to be confirmed by the owner before creating anything)

The app is for **licensees only** (no admin, no club admin), so "Manager" would mislead. The public brand of the product is
already "Mon Club de Tir à l'Arc" (web page title, sender of every email, domain `monclubdetiralarc.fr`).

| What | Proposal |
|---|---|
| Store display name (French) | **Mon Club de Tir à l'Arc** (23 characters, under both stores' 30-character limit) |
| Home-screen label | **Mon Club** |
| Repository (English, technical) | **`myarcheryclub-app`** (matches the existing `MYARCHERYCLUB` storage identifier; alternative `archery-member-app`) |
| Expo slug / deep-link scheme | `myarcheryclub` / `monclubdetiralarc://` (plus universal links on `monclubdetiralarc.fr`) |
| iOS bundle identifier / Android package | `fr.monclubdetiralarc.app` (only if the owner controls that domain) |

The club's own name and colors come from the API (`GET /club`), so the app itself stays generic across clubs.

**Check the current documentation before pinning versions** (Expo SDK, Expo Router, TanStack Query, orval...). The
owner wants documentation URLs for anything newer than your knowledge, so ask for them instead of assuming APIs.

## 3. The API contract

- File: `docs/api/openapi.yaml` in `dehy/archery-manager` (OpenAPI 3.1). Fetch it with
  `gh api repos/dehy/archery-manager/contents/docs/api/openapi.yaml --jq .content | base64 -d`
  (check which branch has it: it lands on `main` with the phase 2 pull request #149; until then use that branch).
  Pin the spec version the client was generated from, and regenerate in CI to detect drift.
- Base path `/api/v1`. The host is configuration (see section 8 for local development).

### Conventions the app must follow

- **Auth: opaque bearer tokens.** `POST /auth/login` returns `access_token` (15 min) and `refresh_token` (30 days).
  Send `Authorization: Bearer <access_token>`.
- **Refresh tokens are single-use.** `POST /auth/refresh` returns a new pair and the old refresh token becomes invalid
  immediately. There is no grace period. Consequences for the app:
  1. **Single-flight refresh**: at most one refresh in progress; every request that got a 401 `invalid_token` waits
     for it, then retries once.
  2. **Persist the new pair atomically before using it**; a crash between refresh and storage must not lose the session.
  3. If the response of a refresh is lost (network), the old refresh token is dead: the user has to log in again. Treat any
     401 from `/auth/refresh` (`invalid_refresh_token`) as "session over": clear tokens, go to login.
  4. `account_locked` (401) on refresh keeps the session on the server: do not wipe the tokens, show the message and retry later.
- **Logout** is `POST /auth/logout` with the **refresh token** in the body (the access token has usually expired). It always
  answers 204. Clear local tokens whatever happens.
- Refresh, logout and health ignore a stale `Authorization` header, login too: attaching the token to every request is fine.
- **Request context headers**: an account can have several licensees (family) and data depends on a season.
  - `X-Licensee`: the FFTA member code of the licensee in use (values come from `GET /me` → `licensees[].ffta_member_code`).
  - `X-Season`: the season as the year it ends (2026/2027 is `2027`; it rolls over on 1 September).
  - Both default to the first licensee and the current season. Keep the selected licensee in app state (persisted) and send the
    headers from one place (the client wrapper). `GET /me` returns the resolved `context`.
- **Errors** always are `{ "error": "<stable_code>", "message": "<English text>" }`. Branch on `error`, never on `message`,
  and never show `message` to users: map codes to French copy in the app. The code list is the `Error.error` enum.
  - `429 too_many_attempts` carries `Retry-After` (seconds). 10 failed logins lock an account for 30 minutes (`account_locked`).
  - `403 forbidden` on club screens usually means "no license for the selected season".
- **Labels.** Domain vocabulary comes as `{ "code", "label" }` with a French label (activities, gender, license types,
  target types...). Workflow states are **raw codes** and the app owns their French labels:
  `participation_state` (`not_going`, `interested`, `registered`; the wording differs for contests, "Je n'y vais pas" /
  "Intéressé" / "Je suis inscrit", and for trainings, "Absent" / "Présent") and club application `status`
  (`pending`, `validated`, `waiting_list`, `rejected`, `cancelled`), event `type`.
- **Privacy.** `display_name` is already what the viewer may see ("Firstname L." for ordinary members, full names for
  admins and coaches). Never rebuild names from other fields, and never search or sort on hidden data.
- **Files** (`picture_url`, attachment `url`) are **paths from the host root** (they already contain `/api/v1/...`), so
  prefix them with the API origin. They need the bearer token: pass headers (`expo-image` accepts
  `source={{ uri, headers }}`; for downloads use `expo-file-system`). They support `If-Modified-Since` / `304`, are served
  `private`, and must be revalidated; a `404` on a picture means "show a placeholder".
- **Pagination** on lists: `page` (from 1) and `per_page` (default 30, capped to 100): `{ data, meta, filters }`.
- Dates are ISO 8601 with offset; plain dates are `YYYY-MM-DD`.

## 4. What the API offers today

Implemented and documented in `openapi.yaml`:

`GET /health` · `POST /auth/login` · `POST /auth/refresh` · `POST /auth/logout` · `GET /me` · `GET /home` ·
`GET /club` · `GET /club/members` · `GET /licensees/{id}` · `GET /licensees/{id}/picture` ·
`GET /licensees/{id}/attachments/{attachmentId}`

`GET /home` has three states (`blank_account`, `no_license`, `dashboard`): model it as a discriminated union.

**Not available yet** (do not mock them as if they existed; ask for them or build the screens behind a clearly
labelled stub):

- registration, email verification and password reset **for the app** (the web has them; the API versions are the next
  piece of API phase 1). Until then the login screen can link to the web for those.
- events and participation (RSVP), results, practice advice, club applications (create, status, cancel), GDPR consent,
  Discord linking, device registration and push notifications, any write endpoint other than the auth ones.
- There is no club map: neither the club nor the API carries coordinates (only events have an address and coordinates).

**Rule: contract first.** If the app needs a field or an endpoint the API lacks, write it down as an API request
(an issue or a short note for the API repository) instead of working around it, and never invent response shapes.

## 5. Working agreements

- Atomic commits, **one-line messages with a gitmoji** (✨ feature, 🐛 fix, ♻️ refactor, ✅ tests, 📝 docs, 🔒 security, 🚨 lint).
  Base commits on `git status` / `git diff`, not on memory of the conversation.
- Run lint, type-check and tests before every commit; never push a red branch. Open pull requests with `gh pr create --body-file`
  (never a multi-line `--body` argument), check CI after pushing, read bot comments (SonarCloud included) and fix them.
- Work in a branch or worktree per phase; do not commit to `main`.
- Create an `AGENTS.md` (or `CLAUDE.md`) in the new repository early and keep it current: commands, conventions, the section 3
  rules, and every setup gap you hit (the owner expects findings to be documented as soon as they are found).
- Be skeptical: the owner has had every piece of API work challenged by independent review agents; expect the same, and prefer
  small, verifiable steps with tests (a mutation check on security-relevant code is welcome).
- UI text goes through i18n from day one (no French strings inline in components).

## 6. Phases of the app (from the plan)

0. **Foundations.** Create the Expo project, TypeScript strict, lint/format/typecheck CI, EAS profiles (development, preview,
   production), generated API client + CI drift check, design tokens, navigation shell, Sentry, i18n.
   *Done when*: a preview build installs on iOS and Android and calls `GET /health`.
1. **Auth and account.** Login, secure token storage, single-flight refresh, logout, `GET /me`, licensee and season switcher via
   headers, account-locked and throttling states; register / verify / reset when the API has them.
   *Done when*: login → switch licensee → logout works on both platforms and a revoked session sends the user to login.
2. **Home, club, members.** `GET /home` (three states), club page, member directory (group filter, search, pagination, pictures
   with headers), licensee profile and attachment viewer (PDF).
   *Done when*: data matches the web for the same account and season, and a forbidden profile shows a proper message.
3. **Events and participation** *(API not ready)*. 4. **Results and practice advice** *(API not ready)*.
5. **Applications, consent, legal, Discord** *(API not ready)*. 6. **Push notifications and offline** *(API not ready)*.
7. **Release hardening.** Accessibility, performance, Maestro E2E, store assets and privacy declarations, TestFlight and Play rollout,
   EAS Update, runbook.

Phases 0, 1 (login part) and 2 can be built now against the real API.

## 7. First session checklist

1. Read this file and `openapi.yaml`; summarise the plan back in a few lines and list the questions you need answered
   (Apple and Google developer accounts, bundle identifier / package name, app name, EAS organisation, Sentry project, Node version).
2. Ask for the documentation URLs of the Expo SDK version you intend to use; pick current stable versions deliberately.
3. Initialise the repository (`create-expo-app`, TypeScript, Expo Router), add `AGENTS.md`, lint/format/typecheck scripts and CI.
4. Add the API client generation from `openapi.yaml`, with a script to refresh it and a CI check that fails on drift.
5. Build the API wrapper first (base URL from config, bearer injection, `X-Licensee` / `X-Season`, error mapping to typed errors,
   single-flight refresh) with unit tests, **before any screen**.
6. Then phase 0's navigation shell and a `/health` call, push, and open the first pull request with a preview build.

## 8. Running the API locally

In the API repository (`dehy/archery-manager`), with Docker: `make start` (app on `http://localhost:8080`, mail catcher on
`http://localhost:1080`), `make deps`, then load the fixtures:

```bash
docker compose exec -u symfony -w /app app bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -u symfony -w /app app bin/console hautelook:fixtures:load --no-interaction
```

- iOS simulator reaches it at `http://localhost:8080`; the **Android emulator** needs `http://10.0.2.2:8080`; a physical device needs
  the computer's LAN address. Local HTTP needs the cleartext exception in the dev build (Android) and an ATS exception (iOS).
- Fixture accounts (all with password `user` unless noted; the fixtures hold licenses for season **2027**):

| Account | Use |
|---|---|
| `user1@ladg.com` … `user10@ladg.com` | ordinary member of the club "Les Archers de Guyenne" (first names are random) |
| `coach@ladg.com` | coach: sees full names, can open profiles of club members |
| `clubadmin@ladg.com` | club admin and coach of the same club |
| `admin@acme.org` (password `admin`) | administrator, licensed in the other club |
| `applicant1@ladg.com` … `applicant5@ladg.com` | licensee **without** a license: `/home` returns `no_license` with a club application (pending, validated, waiting list, rejected, ...) |
| `adult1@ladb.com` … `adult10@ladb.com` | member of another club ("Les Archers du Bosquet") |

  A "blank account" (no licensee) has no fixture: create a user without licensee to see `blank_account`.
- The contract tests of the API repository run `ApiContractTest` against these fixtures; do not rely on random names.

## 9. Pitfalls already met on the API side

- Season semantics trip people up: `2027` is the 2026/2027 season and starts on 1 September 2026.
- Opaque tokens are not JWTs: never try to decode them or read an expiry from them; use `expires_in` from the login/refresh response.
- A `GET /club/members` page past the end is simply empty; `page` above 100000 or `group` of another club is a 400.
- Profile and attachment access needs a shared club with the person **now** (current season); pictures and the directory follow the
  selected season. A 403 is an expected answer, not a bug.
- The roles of coaches and club admins are account-wide (a known limit shared with the web), so an account that is coach in one club
  and holds a child's license in another behaves as coach in both.
- Do not cache JSON responses across users or after logout: clear the query cache on logout and when switching licensee.
