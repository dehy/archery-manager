# Archery Manager — GitHub Copilot Instructions

**Archery Manager** is a Symfony 7.4 web application for managing an archery club (Les Archers de Bordeaux Guyenne, AGPL v3). It handles member management, event scheduling, FFTA federation synchronization, equipment tracking, club applications, and GDPR consent.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| PHP | 8.4+ (strict_types, readonly properties, typed constants) |
| Framework | Symfony 7.4.* |
| Database | MariaDB 12.1 + Doctrine ORM 3.x |
| API | API Platform 3.x |
| Templates | Twig 3.x |
| CSS | Bootstrap 5.3 + custom SCSS |
| JavaScript | TypeScript + Stimulus (Hotwired) |
| Icons | Font Awesome 7.1+ Pro |
| Build | Webpack Encore 4.x |
| Auth | Symfony Security (form login, remember me, impersonation) |
| Messaging | Symfony Messenger + Scheduler |

## Critical Rule: Docker-First

**ALL PHP/Symfony/Composer commands MUST run inside the Docker container.**

```bash
docker compose exec -u symfony -w /app app bin/console <command>
docker compose exec -u symfony -w /app app composer <command>
docker compose exec -u symfony -w /app app vendor/bin/phpunit
# Or: make shell → then run commands inside
```

## Pre-Commit Checklist

1. `make qa` — Rector + PHP CS Fixer + PHPStan (repeat until passes)
2. `docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled`
3. Fix any issues and repeat until both pass

## Git Conventions

- **One-line commit messages with gitmoji**: `✨ Add club equipment loan tracking`
- **Atomic commits**: one logical unit per commit
- **ALWAYS base on `git status`**, never on conversation history
- Gitmoji: `✨` feature · `🐛` bug · `♻️` refactor · `✅` tests · `🎨` formatting · `🔥` removal · `💄` UI

## 10 Critical Rules

1. Run ALL commands inside Docker container (`docker compose exec -u symfony -w /app app ...`)
2. Run `make qa` + tests before every commit
3. Always generate and review migrations after entity changes (`bin/console make:migration`)
4. Use DBAL types (e.g., `EventParticipationStateType`) — never raw strings for enums
5. Use service layer for business logic (EventHelper, LicenseHelper, SeasonHelper …) — not controllers
6. Always check group-based event authorization via `EventHelper::canLicenseeParticipateInEvent()`
7. Font Awesome icons: import in `assets/app.ts` → `library.add()` → `yarn run encore dev` (icons won't render without rebuild)
8. Prefer frontend controllers/templates over EasyAdmin pages
9. Use Bootstrap utilities before writing custom CSS
10. Specify `bootstrap_5_layout.html.twig` form theme for consistency

## Scoped Instruction Files

These files load automatically in VS Code based on the file you're editing:

| File | Applies to | Content |
|------|-----------|---------|
| [backend.instructions.md](.github/instructions/backend.instructions.md) | `**/*.php` | Symfony/Doctrine patterns, entities, DBAL types, SonarQube PHP rules, security |
| [frontend.instructions.md](.github/instructions/frontend.instructions.md) | `assets/**` | Font Awesome workflow, Stimulus controllers, Bootstrap, SCSS, SonarQube TS rules |
| [testing.instructions.md](.github/instructions/testing.instructions.md) | `tests/**` | Test commands, DAMA bundle, fixtures, test patterns |
| [templates.instructions.md](.github/instructions/templates.instructions.md) | `templates/**` | Twig forms, modals, Bootstrap components, authorization patterns |

For full architecture, domain model, and business logic details see [AGENTS.md](../../AGENTS.md).

## Domain Vocabulary

| Term | Meaning |
|------|---------|
| Licensee | Club member / archer profile |
| License | Annual membership for a season |
| Season | Archery year (Sept–Aug), e.g., Season 2025 = Sept 2025 – Aug 2026 |
| Trombinoscope | Member photo directory (French term) |
| FFTA | Fédération Française de Tir à l'Arc (French Archery Federation) |
| Espace Dirigeant | FFTA club manager portal (data sync source) |
| Mon Espace | FFTA individual member portal |
| Club Application | Membership application (pending/validated/waiting_list/rejected/cancelled) |
| Age category | Benjamins / Minimes / Cadets / Juniors / Seniors / etc. |
