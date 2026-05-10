# Archery Manager

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=dehy_archery-manager&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=dehy_archery-manager)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager?ref=badge_shield)

A web application for managing an archery club — built for [Les Archers de Bordeaux Guyenne](https://archersdebordeaux-guyenne.com), free and open-source under the AGPL v3 licence.

---

## For Club Members & Coaches

Archery Manager is the tool that keeps your club organised. Here is what it does:

### Member Directory (Trombinoscope)
Browse and search the full list of club members with photos, contact details, and current licence information. Profiles are synchronised automatically with the FFTA (French Archery Federation) so data stays up to date without manual entry.

### Events & Participation
- **Training sessions** — see who is coming, mark yourself present or absent.
- **Competitions** — register for contests, choose your target type and departure time, track whether you are going, interested, or not attending.
- **Calendar view** — all upcoming events in one place, filterable by type.

### Equipment
Track your bow and arrow configurations, record sight adjustments for different distances, and consult the club's equipment inventory.

### Competition Results
Results from official contests can be imported and browsed by season. Scores and rankings are stored per archer.

### Club Applications
New members can submit a membership application online. Admins can approve, place on a waiting list, or reject applications.

### Training Resources
Coaches can publish practice advice and attach documents (PDFs, images) that members can access at any time.

### Contribute or Give Feedback
Whether you are an archer, a coach, or someone with ideas for improvement, your input is welcome — technical or not.

- Email: [archery-manager@admds.net](mailto:archery-manager@admds.net)
- GitHub Issues: [github.com/dehy/archery-manager/issues](https://github.com/dehy/archery-manager/issues)

---

## For Developers

### Tech Stack

| Layer | Technology |
|-------|-----------|
| PHP | 8.5+ (strict types, readonly properties) |
| Framework | Symfony 7.4 |
| Database | MariaDB 12.1 + Doctrine ORM 3.x |
| API | API Platform 3.x |
| Templates | Twig 3.x |
| CSS | Bootstrap 5.3 + custom SCSS |
| JavaScript | TypeScript + Stimulus (Hotwired) |
| Icons | Font Awesome 7.2+ Free |
| Build | Webpack Encore 4.x |
| Auth | Symfony Security (form login, remember me, impersonation) |
| Messaging | Symfony Messenger + Scheduler |
| Containerisation | Docker Compose + FrankenPHP |

### Running Locally

**Prerequisites**: Docker and Docker Compose installed.

```shell
# Build (if needed) and start all containers
make start

# Install PHP and JavaScript dependencies
make deps
```

Services available at:

| Service | URL |
|---------|-----|
| Application | http://localhost:8080 |
| Adminer (database GUI) | http://localhost:8081 |
| Mailcatcher (email preview) | http://localhost:1080 |

#### Database access (Adminer)

- **Type**: MySQL
- **Server**: `database`
- **Username**: `symfony`
- **Password**: `ChangeMe`
- **Database**: `app`

### Running Commands

All PHP/Symfony/Composer commands must run **inside the Docker container**:

```shell
docker compose exec -u symfony -w /app app bin/console <command>
docker compose exec -u symfony -w /app app composer <command>

# Or open an interactive shell
make shell
```

### Database Migrations

```shell
# Generate a migration after changing entities
docker compose exec -u symfony -w /app app bin/console make:migration

# Apply pending migrations
make migratedb
```

### Code Quality

```shell
# Run Rector + PHP CS Fixer + PHPStan
make qa
```

All three tools must pass before committing.

### Testing

```shell
# Full CI-equivalent run (recommended before pushing)
make test

# Quick local loop — recreate test DB, load fixtures, run suite
docker compose exec -u symfony -w /app app sh -c '
  APP_ENV=test bin/console doctrine:database:drop --force --if-exists &&
  APP_ENV=test bin/console doctrine:database:create &&
  APP_ENV=test bin/console doctrine:migrations:migrate --no-interaction
'
docker compose exec -u symfony -w /app app sh -c \
  'APP_ENV=test bin/console hautelook:fixtures:load --no-interaction'
docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled
```

### FFTA Integration

The application synchronises member and licence data from the FFTA "Espace Dirigeant" portal via a custom web scraper (`FftaScrapper`). Synchronisation can be triggered manually from the admin panel or runs automatically via the `scheduler-ffta-licensees` Docker service.

### Makefile Reference

| Command | Description |
|---------|-------------|
| `make start` | Build images (if needed) and start services |
| `make stop` | Stop all services |
| `make shell` | Open a shell as the `symfony` user |
| `make deps` | Install PHP + JS dependencies |
| `make qa` | Run all code quality tools |
| `make migratedb` | Apply database migrations |
| `make test` | Run the full test suite in an isolated container |

### Project Structure

```
src/
  Controller/     HTTP controllers
  Entity/         Doctrine entities (domain model)
  Form/           Symfony form types
  Repository/     Custom Doctrine queries
  Helper/         Business logic services
  DBAL/Types/     Custom Doctrine enum types
  Command/        Console commands (FFTA sync, result import…)
  Scrapper/       FFTA web scraping
assets/
  controllers/    Stimulus controllers (TypeScript)
  styles/         SCSS stylesheets
templates/        Twig templates
migrations/       Doctrine migrations
fixtures/         Test data (YAML)
docker/           Dockerfiles, PHP/Nginx config, build scripts
```

---

## Licence

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager?ref=badge_large)