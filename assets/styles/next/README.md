# Redesigned interface styles

Stylesheet of the redesigned interface (redesign roadmap, milestone M2). It is built from the canvas UI kit
(`kit.css` in the design canvas) and turned on per user from **Mon compte → Nouvelle interface (bêta)**.

| File | Content |
|---|---|
| `_tokens.scss` | Kit tokens (`$am-*`) and the Bootstrap variables they drive |
| `_status.scss` | Status chips `.am-status-{ok,maybe,no,bad,info}` and `.am-tag` |
| `_shell.scss` | Sidebar, account block, mobile tab bar (`templates/base_next.html.twig`) |
| `_agenda.scss` | Page heading `.am-page-head`, next-event hero `.am-hero`, event list `.am-agenda`, `.am-facts`, `.am-card-sand` |
| `_event.scss` | Event page: back link `.am-crumb`, answer choices as option cards `.am-options` / `.am-option` |
| `app.scss` | Entry: tokens → Bootstrap → shared page styles (`../_events`, `../_pages`) → status → shell → agenda → event |

Encore entry: `theme_next` (`assets/theme_next.ts`, which also loads the self-hosted fonts). Scripts stay in `app`.

## How pages pick it up

`templates/base.html.twig` extends `base_next.html.twig` when `app.user.hasNewInterface`, `base_legacy.html.twig`
otherwise. Page bodies don't change: Bootstrap classes take the kit's colours, radii and fonts from the variables.
A page migrated in M3 to M6 gets its own `*_next.html.twig` body, which its controller renders for opted-in users
(`homepage/index_next.html.twig`, `event/show_next.html.twig`), and uses the `am-*` classes directly. Event pieces shared by the
screens that list events (type, date line, status chip, one-tap presence) are macros in
`templates/_next/event_macros.html.twig`.

## Tokens

| Token | Value | Use |
|---|---|---|
| `$am-ground` | `#FBF3E4` | Page background |
| `$am-surface` | `#FFFFFF` | Cards, menus |
| `$am-sand` | `#F4E8D2` | Sidebar, quiet cards |
| `$am-ink` | `#231B16` | Text, dark surfaces |
| `$am-soft` | `#5C5048` | Secondary text |
| `$am-line` | `#E6D6B8` | Borders |
| `$am-field-border` | `#8C7C66` | Form field borders (4:1 on white) |
| `$am-accent` | `#C93A0C` | Primary buttons, current item (white text 5.1:1) |
| `$am-accent-text` | `#B5330A` | Links, accent text |

Statuses (text / background): ok `#14503F` / `#DDF0E8`, maybe `#6B4600` / `#FBE6B8`, no `#4A3F37` / `#E9E4DD`,
bad `#9A2323` / `#FBE0DE`, info `#1E3C9E` / `#E1E8F8`. A status always carries an icon and a label.

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
