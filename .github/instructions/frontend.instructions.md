---
applyTo: "assets/**"
---

# Frontend Coding Instructions (TypeScript / SCSS / Stimulus)

## Font Awesome — CRITICAL WORKFLOW

Every time you add a Font Awesome icon to a template, you MUST do all 3 steps:

**Step 1** — Add the import in `assets/app.ts`:
```typescript
import {
    // ... existing imports ...
    faYourNewIcon,
} from "@fortawesome/free-solid-svg-icons";
```

**Step 2** — Add to `library.add()` in `assets/app.ts`:
```typescript
library.add(
    // ... existing icons ...
    faYourNewIcon,
);
```

**Step 3** — Rebuild:
```bash
docker compose exec -u symfony -w /app app yarn run encore dev
```

**Icons will not render without the rebuild.** You will see empty spaces where icons should appear.

### Available Icon Packs

| Package | Weight | Template class | Currently imported? |
|---------|--------|---------------|-------------------|
| `@fortawesome/free-solid-svg-icons` | Solid | `fa-solid` | ✅ Yes |
| `@fortawesome/free-brands-svg-icons` | Brands | `fa-brands` | ✅ Yes (5 icons: Apple, Discord, Google, Microsoft, Waze) |
| `@fortawesome/free-regular-svg-icons` | Regular | `fa-regular` | ⚠️ Minimal (1 icon: faFile) |

To use additional free packs, add the import to `assets/app.ts` first.

### Icon Naming Convention

Template `fa-user-gear` → TypeScript import `faUserGear` (camelCase, drop `fa-` prefix)

```twig
<em class="fa-solid fa-arrow-right me-2"></em>  → faArrowRight
<em class="fa-solid fa-circle-info"></em>        → faCircleInfo
<em class="fa-brands fa-discord me-2"></em>      → faDiscord
```

## Stimulus Controllers

Existing controllers in `assets/controllers/`:

| File | `data-controller` | Purpose |
|------|------------------|---------|
| `modal_controller.ts` | `modal` | Generic reusable modal; opens via AJAX, handles form submissions |
| `event-participation_controller.ts` | `event-participation` | Event participation state updates via AJAX |
| `event_highlight_controller.ts` | `event-highlight` | Calendar/list view hover sync for events |
| `license_form_controller.ts` | `license-form` | Cascading dropdowns: age category → category → type |
| `results-chart_controller.ts` | `results-chart` | Chart.js lifecycle hook integration |
| `password-strength_controller.ts` | `password-strength` | Real-time zxcvbn password strength meter |

### Writing New Stimulus Controllers

```typescript
// ✅ Named class matching filename (SonarQube S4212)
export default class MyFeatureController extends Controller {
    // ✅ readonly on static properties that are never reassigned (SonarQube S3827)
    static readonly targets = ["input", "output"];
    static readonly values = { url: String };

    connect(): void {
        // ...
    }
}
```

### SonarQube TypeScript Rules

**S4212 — Name all classes:**
```typescript
// ❌ Bad
export default class extends Controller { ... }

// ✅ Good — name matches filename (e.g., modal_controller.ts)
export default class ModalController extends Controller { ... }
```

**S3827 — Use `readonly` for static properties:**
```typescript
// ❌ Bad
static targets = ["target"]
static values = { url: String }

// ✅ Good
static readonly targets = ["target"]
static readonly values = { url: String }
```

**S6582 — Use optional chaining:**
```typescript
// ❌ Bad
if (this.hasOutputTarget && this.outputTarget) this.outputTarget.textContent = x;

// ✅ Good
this.outputTarget?.textContent = x;
```

## Bootstrap 5 Quick Reference

```html
<!-- Layout -->
<div class="container">
  <div class="row">
    <div class="col-lg-4 col-12">...</div>  <!-- 4 cols desktop / full width mobile -->
    <div class="col-lg-8 col-12">...</div>
  </div>
</div>

<!-- Spacing: m-{n}, p-{n}, mb-3, mt-2, me-2 (margin-end), ms-2 (margin-start) -->
<!-- Flex: d-flex, justify-content-between, align-items-center, gap-2, flex-wrap -->
<!-- Display: d-none, d-md-none (hide on desktop), d-none d-md-block (hide on mobile) -->
<!-- Colors: text-*, bg-*, btn-{primary|secondary|success|danger|warning|info} -->

<!-- Card -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><em class="fa-solid fa-icon me-2"></em>Title</h5>
    </div>
    <div class="card-body">...</div>
</div>

<!-- Button with icon -->
<button class="btn btn-primary d-flex align-items-center">
    <em class="fa-solid fa-save me-2"></em> Save
</button>

<!-- Disabled button with tooltip -->
<button class="btn btn-secondary" disabled
        data-bs-toggle="tooltip"
        title="Reason why action is unavailable">
    Action
</button>

<!-- Empty state -->
<div class="text-center text-muted py-4">
    <em class="fa-solid fa-inbox fa-3x mb-3 d-block"></em>
    <p class="mb-0">Aucun élément trouvé</p>
</div>
```

## SCSS Organization

`assets/styles/app.scss` import order:
```scss
@import "variables";           // Custom Bootstrap variable overrides (colors, etc.)
@import "bootstrap";           // Full Bootstrap
@import "bootstrap/scss/functions";
@import "bootstrap/scss/variables";
@import "bootstrap/scss/maps";
@import "bootstrap/scss/mixins";
@import "bootstrap/scss/utilities";
@import "mobile";              // Fixed bottom nav, mobile-specific styles
@import "events";              // Event list and card styles
@import "_calendar.scss";      // Calendar view
@import "_event_participation.scss";  // Participation state colors (extends bg-*)
```

Always import Bootstrap functions/variables/mixins before using them in custom files.

## Webpack Encore Commands

```bash
# Development (with source maps)
docker compose exec -u symfony -w /app app yarn run encore dev

# Watch mode (auto-rebuild on save)
docker compose exec -u symfony -w /app app yarn run encore dev --watch

# Production (minified, optimized)
docker compose exec -u symfony -w /app app yarn run encore production
```

## General Rules

- Prefer Bootstrap utilities over writing custom CSS
- Register new Stimulus controllers in `assets/controllers.json` if using Symfony UX convention
- Never use `static` properties without `readonly` unless values change at runtime
