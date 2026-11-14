# Redesigned interface styles

Stylesheet of the redesigned interface (redesign roadmap, milestone M2). It is built from the canvas UI kit
(`kit.css` in the design canvas) and turned on per user from **Mon compte → Nouvelle interface (bêta)**.

| File | Content |
|---|---|
| `_tokens.scss` | Colour tokens of the four looks (maps `$am-base-*`, `$am-look-*`), and the Bootstrap variables they drive |
| `_themes.scss` | Tokens as CSS custom properties (`--am-*`) per look, Bootstrap's runtime variables in dark; from here on `$am-*` are `var(--am-*)` |
| `_status.scss` | Status chips `.am-status-{ok,maybe,no,bad,info}` and `.am-tag` |
| `_shell.scss` | Sidebar, account block, mobile tab bar (`templates/base_next.html.twig`) |
| `_agenda.scss` | Page heading `.am-page-head`, next-event hero `.am-hero`, event list `.am-agenda`, `.am-facts`, `.am-card-sand` |
| `_event.scss` | Event page: back link `.am-crumb`, answer choices as option cards `.am-options` / `.am-option` |
| `_calendar.scss` | Calendar: kinds of event `.am-type-{contest,training,free,other}`, month grid `.am-calendar-*` |
| `_appearance.scss` | Mon compte → Apparence: theme cards with their preview, mode segmented control |
| `app.scss` | Entry: tokens → Bootstrap → shared page styles (`../_events`, `../_pages`) → themes → status → shell → agenda → event → calendar → appearance |

Encore entry: `theme_next` (`assets/theme_next.ts`, which also loads the self-hosted fonts). Scripts stay in `app`.

## How pages pick it up

`templates/base.html.twig` extends `base_next.html.twig` when `app.user.hasNewInterface`, `base_legacy.html.twig`
otherwise. Page bodies don't change: Bootstrap classes take the kit's colours, radii and fonts from the variables.
A page migrated in M3 to M6 gets its own `*_next.html.twig` body, which its controller renders for opted-in users
(`homepage/index_next.html.twig`, `event/show_next.html.twig`, `event/index_next.html.twig`), and uses the `am-*` classes directly. Event pieces shared by the
screens that list events (type, date line, status chip, one-tap presence) are macros in
`templates/_next/event_macros.html.twig`.

## Themes and tokens

Two themes, each in a light and a dark version, chosen per user in **Mon compte → Apparence**
(`User::interfaceTheme`, `User::interfaceMode`, page `user/appearance_next.html.twig`):

| Look | Theme | Mode |
|---|---|---|
| `soft` | Doux (default): warm cream, sand sidebar, ink hero card, neutral icons | light |
| `colorful` | Coloré: orange hero card, one tint per kind of event, coloured sidebar icons, licence strip | light |
| `soft-dark`, `colorful-dark` | the same in dark | dark |

`<html data-am-theme="soft|colorful" data-am-mode="system|light|dark">` selects the look; *system* follows
`prefers-color-scheme` in CSS only (no script, nothing to allow in the CSP, no flash on load). An element carrying
both attributes takes its own look (the previews of the Apparence page).

Every colour is a token of `_tokens.scss`, written once as a literal: `$am-base-light` / `$am-base-dark` (shared by
both themes: ground, surface, sand, ink, soft, line, row, field, accent…, statuses) and `$am-look-<look>` (hero-*,
nav-*, advice-*, strip, kind-*, tile-*). `_themes.scss` turns them into `--am-<name>` (plus `--am-<name>-rgb`).

- **Rule for the am-* styles**: tokens only, `var(--am-*)` or `$am-*` after `_themes.scss`. No literal colour, no
  `rgba()` of a fixed colour, no per-theme markup: add a token to the four looks instead.
- **Bootstrap**: compiled with the light base tokens, which both themes share, so light needs nothing more (Doux
  light is exactly as before the themes). In dark, `_themes.scss` points Bootstrap's runtime variables (body, links,
  borders, alerts, cards, dropdowns, modals, list groups, forms, buttons) at the tokens.
- **Contrast**: `tests/Unit/Theme/ThemeContrastTest.php` reads the maps and checks every text pair (4.5:1) and icon
  or border (3:1) in the four looks; it fails when a look misses a token the others have.
- **Adding a theme**: a `$am-look-<name>` and a `$am-look-<name>-dark` map (same keys as the others), two selectors in
  `_themes.scss`, a case of `App\Enum\InterfaceTheme` (label, description), the looks of `ThemeContrastTest`.

Base tokens, light / dark:

| Token | Light | Dark | Use |
|---|---|---|---|
| `ground` | `#FBF3E4` | `#17120F` | Page background |
| `surface` | `#FFFFFF` | `#231B16` | Cards, menus |
| `sand` | `#F4E8D2` | `#2C231D` | Sidebar, quiet cards |
| `ink` | `#231B16` | `#F3EBE0` | Text |
| `soft` | `#5C5048` | `#B9AC9E` | Secondary text |
| `line` | `#E6D6B8` | `#3A2F27` | Borders (decorative) |
| `field` | `#8C7C66` | `#9C8D7A` | Form field borders |
| `accent` | `#C93A0C` | `#FF8A5C` | Primary buttons, current item (text on it: `on-accent`, white / near black) |
| `accent-text` | `#B5330A` | `#FF8A5C` | Links, accent text |

Statuses (text / background), light: ok `#14503F` / `#DDF0E8`, maybe `#6B4600` / `#FBE6B8`, no `#4A3F37` / `#E9E4DD`,
bad `#9A2323` / `#FBE0DE`, info `#1E3C9E` / `#E1E8F8`; dark: ok `#8FD9B8` / `#17382D`, maybe `#F5C86A` / `#3D2F10`,
no `#D6CCC0` / `#3A3430`, bad `#FFB3AD` / `#4A1F1F`, info `#A9BDFA` / `#1C2A5C`. A status always carries an icon and
a label.

Fonts: Inter (body) and Bricolage Grotesque (headings), self-hosted with `@fontsource-variable/*`, so no visitor
request goes to a third-party font service.

## Kit icons → Font Awesome

The kit draws its own icons; the app keeps Font Awesome (register new ones in `assets/app.ts`).

| Kit | Font Awesome | Kit | Font Awesome |
|---|---|---|---|
| `i-home` | `fa-house` | `i-file` | `fa-file` |
| `i-cal` | `fa-calendar` | `i-search` | `fa-magnifying-glass` |
| `i-users` | `fa-users` | `i-chev` | `fa-chevron-right` |
| `i-user` | `fa-user` | `i-back` | `fa-chevron-left` / `fa-arrow-left` |
| `i-club` | `fa-building` | `i-edit` | `fa-pen-to-square` |
| `i-bow` | `fa-bullseye` | `i-cog` | `fa-gear` (`fa-cog`) |
| `i-trophy` | `fa-trophy` | `i-mail` | `fa-envelope` |
| `i-plus` | `fa-plus` | `i-down` / `i-up` | `fa-download` / `fa-upload` |
| `i-check` | `fa-check` | `i-trash` | `fa-trash` |
| `i-x` | `fa-xmark` | `i-dots` | `fa-ellipsis` |
| `i-clock` | `fa-clock` | `i-lock` | `fa-lock` |
| `i-pin` | `fa-map-marker-alt` | `i-flag` | `fa-calendar-check` (event management) |

## Kit classes → Bootstrap

| Kit | Bootstrap (with these tokens) |
|---|---|
| `.btn` / `.btn.s` / `.btn.g` / `.btn.d` | `.btn-primary` / `.btn-outline-dark` / `.btn-link` / `.btn-outline-danger` |
| `.btn.sm`, `.btn.block` | `.btn-sm`, `.w-100` |
| `.card`, `.card.flat` | `.card` + `.card-body`, `.card` + `.list-group-flush` |
| `.chip.*` | `.am-status-*` (this folder) |
| `.banner.*` | `.alert-warning` / `-info` / `-success` / `-danger` |
| `.inp`, `.sel`, `.txt` | `.form-control`, `.form-select`, `textarea.form-control` |
| `.tabs`, `.seg` | `.nav-underline`, `.nav-pills` |
| `.tbl` | `.table` |
| `.menu` | `.dropdown-menu` |
| `.g2` / `.g3` / `.g4`, `.cols` | `.row` + `.col-md-6` / `-4` / `-3`, `.row` + `.col-lg-8` / `.col-lg-4` |
