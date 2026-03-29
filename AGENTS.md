# AI Agents & Development Notes

This document contains important context and guidelines for AI coding assistants working on this project.

## Project Overview

**Archery Manager** is a comprehensive web application for managing an archery club. It was created for [Les Archers de Bordeaux Guyenne](https://archersdebordeaux-guyenne.com) and is licensed under AGPL v3.

### Core Features
- **Member Management**: Licensee profiles, licenses, and user accounts
- **Event Management**: Contests, trainings, hobby contests, free trainings
- **Group Organization**: Group-based permissions and event assignments
- **Participation Tracking**: Event participation with dynamic states
- **Equipment Management**: Bows, arrows, sight adjustments
- **Results & Competitions**: Contest results tracking and imports
- **FFTA Integration**: Synchronization with French Archery Federation (FFTA) system
- **Practice Advice**: Training tips and attachments
- **Club Applications**: Membership application management (pending/validated/waiting_list/rejected/cancelled)
- **GDPR Compliance**: Cookie consent tracking (ConsentLog entity with anonymized audit trail)
- **Discord Integration**: Bot for club communication
- **Season Management**: Multi-season support with automatic season detection

## Scoped Instruction Files (VS Code Copilot)

For language-specific patterns, refer to the scoped instruction files in `.github/instructions/`. These load automatically in VS Code based on the file you're editing:

| File | Applies to | Content |
|------|-----------|--------|
| [backend.instructions.md](.github/instructions/backend.instructions.md) | `**/*.php` | Symfony/Doctrine patterns, entities, DBAL types, SonarQube PHP rules |
| [frontend.instructions.md](.github/instructions/frontend.instructions.md) | `assets/**` | Font Awesome workflow, Stimulus controllers, Bootstrap, SCSS |
| [testing.instructions.md](.github/instructions/testing.instructions.md) | `tests/**` | Test commands, DAMA, fixtures, patterns |
| [templates.instructions.md](.github/instructions/templates.instructions.md) | `templates/**` | Twig forms, modals, Bootstrap components, auth |

## Technology Stack

### Backend
- **Framework**: Symfony 7.4.*
- **PHP**: 8.4+ (with typed class constants, strict types)
- **Database**: MariaDB 12.1 with Doctrine ORM 3.x
- **API**: API Platform 3.x for REST APIs
- **Authentication**: Symfony Security with form login, remember me, user switching
- **Password Reset**: SymfonyCasts ResetPasswordBundle
- **Email Verification**: SymfonyCasts VerifyEmailBundle
- **File Storage**: Flysystem with S3 support (async-aws)
- **Email**: Symfony Mailer with Mailgun/Scaleway providers
- **Encryption**: SpecShaper EncryptBundle for sensitive data
- **Audit**: DamienHarper AuditorBundle for entity change tracking
- **Scheduling**: Symfony Scheduler for recurring tasks
- **Messaging**: Symfony Messenger for async tasks

### Frontend
- **Templates**: Twig 3.x
- **JavaScript**: TypeScript with Stimulus (Hotwired)
- **CSS**: Bootstrap 5.3+ with custom SCSS
- **Icons**: Font Awesome 7.2+ Free (Solid/Brands actively used; Regular minimally used — see `assets/app.ts` for currently imported icons)
- **Build Tool**: Webpack Encore 4.x
- **Charts**: Chart.js 3.x with annotation and datalabels plugins
- **Maps**: Leaflet 1.9+ with Geocoder (Mapbox provider)
- **PDF**: PDF.js 4.x for rendering, Smalot PDF Parser for parsing
- **HTTP Client**: Axios

### Development Tools
- **Containerization**: Docker Compose
- **Code Quality**: Rector, PHP CS Fixer (PSR-12), PHPStan
- **Testing**: PHPUnit
- **Static Analysis**: SonarCloud
- **Error Tracking**: Sentry
- **Observability**: Grafana Faro (web SDK + tracing)
- **Package Manager**: npm

## Architecture

### Domain Model

#### Core Entities
- **User**: Authentication account (email-based)
- **Licensee**: Archer profile (can have multiple licenses across seasons)
  - Properties: gender, name, birthdate, FFTA member code/ID, profile picture
  - Relationships: belongs to User, has many Licenses, belongs to many Groups
  - FFTA integration: Synced from FFTA "Espace Dirigeant"
- **License**: Annual membership for a season
  - Properties: season, type (competitive/recreational), category, age category, activities, club
  - Validation: Unique per (licensee, season)
- **Club**: Archery club entity
  - Properties: name, FFTA credentials for sync, address, contact info
- **Season**: Represents archery season (e.g., 2025)
  - Auto-detection: `Season::seasonForDate()` determines current season
- **Group**: Organizational unit for licensees
  - Use cases: Training groups, competition teams, age groups
  - Relationships: Many-to-many with Licensees and Events

#### Event Hierarchy
- **Event** (abstract base class)
  - Common properties: name, description, starts/ends at, location, assigned groups
  - **ContestEvent**: Official competitions with FFTA integration
    - Properties: contest type, discipline, FFTA event ID
    - Features: Results tracking, target types, departures
  - **HobbyContestEvent**: Informal competitions
    - Similar to ContestEvent but no FFTA sync
  - **TrainingEvent**: Scheduled training sessions
    - Features: Default attendance for group members
  - **FreeTrainingEvent**: Open practice sessions

#### Participation & Results
- **EventParticipation**: Links Licensee to Event
  - Properties: participation state, activity, target type (contests), departure time (contests)
  - States: NOT_GOING, INTERESTED (contests only), REGISTERED
  - Dynamic defaults: Training events default to REGISTERED for authorized licensees
- **Result**: Contest performance records
  - Properties: scores, rankings, FFTA sync status
  - Import: ARC format supported via command

#### Equipment
- **Bow**: Archer's bow configuration
  - Properties: bow type, draw weight, length, brand/model
- **Arrow**: Arrow specifications
  - Properties: arrow type, spine, length, fletching
- **SightAdjustment**: Sight settings for different distances
  - Helps archers track and replicate successful configurations

#### Attachments & Media
- **Attachment**: Base class for file uploads
  - **EventAttachment**: Files attached to events
  - **LicenseeAttachment**: Files attached to licensee profiles (medical certs, photos, etc.)
  - **PracticeAdviceAttachment**: Files for training resources
- **PracticeAdvice**: Training tips and guides

#### GDPR & Club Management
- **ConsentLog**: GDPR consent audit trail
  - Properties: services (JSON array), action (accepted/declined/partial), policyVersion, ipAddressAnonymized, userAgent, createdAt
  - Linked to User (nullable, CASCADE delete)
- **ClubApplication**: Membership application (replaces old LicenseApplication)
  - Properties: licensee, club, season, status (ClubApplicationStatusType), adminMessage, createdAt, updatedAt, processedBy
  - Status flow: PENDING → VALIDATED / WAITING_LIST / REJECTED / CANCELLED
- **ClubEquipment**: Club-owned gear inventory
  - Properties: club, type (ClubEquipmentType), name, serialNumber, quantity, purchasePrice, purchaseDate, bowType, brand, model, arrowType
- **EquipmentLoan**: Tracks gear loans from club to members
  - Properties: equipment, borrower (Licensee), startDate, returnDate, quantity, notes, createdBy (User)

### DBAL Types (Enums)

Custom Doctrine DBAL types for type-safe enumerations:
- **ArrowType**: Arrow categories
- **BowType**: Recurve, Compound, Longbow, Barebow
- **ContestType**: Competition formats
- **DisciplineType**: Archery disciplines (Indoor, Outdoor, Field, 3D, etc.)
- **EventParticipationStateType**: NOT_GOING, INTERESTED, REGISTERED
- **EventType**: Event categories
- **FletchingType**: Fletching configurations
- **GenderType**: Male, Female, Other
- **LicenseActivityType**: Practice activities (Target, Field, 3D, etc.)
- **LicenseAgeCategoryType**: Age groups (Benjamins, Minimes, Cadets, etc.)
- **LicenseCategoryType**: Competition categories
- **LicenseType**: Competitive vs recreational
- **LicenseeAttachmentType**: Attachment categories
- **PracticeLevelType**: Beginner, Intermediate, Advanced
- **TargetTypeType**: Target face types (80cm, 122cm, etc.)
- **UserRoleType**: User roles
- **ClubApplicationStatusType**: PENDING, VALIDATED, WAITING_LIST, REJECTED, CANCELLED
- **ClubEquipmentType**: Equipment type classification
- **EventAttachmentType**: Event attachment categories
- **PracticeAdviceAttachmentType**: Training advice attachment types

### Service Layer

#### Helpers
- **EventHelper**: Event business logic
  - `canLicenseeParticipateInEvent()`: Group-based authorization
  - `getAllParticipantsForEvent()`: Returns all participants including virtual defaults
  - `licenseeParticipationToEvent()`: Creates/retrieves participation with defaults
- **LicenseeHelper**: Licensee utilities
  - Current licensee from session
  - Display name formatting
- **LicenseHelper**: License business logic
  - Current license detection
  - Season-based queries
- **SeasonHelper**: Season management
  - Current season detection
  - Season selection
- **ClubHelper**: Club-related utilities
- **FftaHelper**: FFTA API integration helpers
- **ResultHelper**: Results processing
- **MapHelper**: Geocoding and mapping
- **EmailHelper**: Email utilities
- **StringHelper**: String manipulation

#### Scrappers
- **FftaScrapper**: Web scraping for FFTA "Espace Dirigeant" and "Mon Espace"
  - Licensee synchronization
  - License data retrieval
  - Event fetching
  - Profile picture downloads
  - 724 lines of complex HTTP client + DOM parsing logic

#### Commands
- **FftaSyncLicenseesCommand**: Bulk sync all licensees from FFTA
- **FftaSyncLicenseeCommand**: Sync individual licensee
- **FftaFetchParticipatingEvent**: Fetch events from FFTA
- **FftaFindId**: Find FFTA ID for licensees
- **RecurringEventGenerateCommand**: Generate recurring events (via Scheduler)
- **ResultArcImportCommand**: Import results from ARC format
- **DiscordBotRunCommand**: Run Discord bot

#### Controllers
- **HomepageController**: Dashboard with quick actions and upcoming events
- **LicenseeController**: Licensee management, trombinoscope (member directory), profiles
- **EventController**: Event CRUD, participation management, calendar views
- **ClubController**: Club information and management
- **GroupManagementController**: Group creation and member assignment
- **LicenseController**: License applications and renewals
- **UserController**: User account management
- **LoginController**: Authentication
- **RegistrationController**: New user registration
- **ResetPasswordController**: Password reset flow
- **PracticeAdviceController**: Training resources
- **DiscordController**: Discord webhook integration
- **PdfController**: PDF generation
- **MobileController**: Mobile-specific views
- **Admin/***: EasyAdmin-based admin panel
- **ClubApplicationController**: Membership application submissions and management
- **ConsentController**: GDPR cookie consent recording (rate-limited, validates action/services)
- **LicenseeManagementController**: Admin licensee management
- **LegalController**: Legal pages (CGU, privacy policy)

### Authorization Model

#### Role Hierarchy
- **ROLE_USER**: Authenticated members (default)
- **ROLE_ADMIN**: Administrators
  - Inherits: ROLE_ALLOWED_TO_SWITCH (user impersonation)

#### Access Control
- **Public routes** (`PUBLIC_ACCESS`):
  - `/login`, `/register`, `/verify/email`, `/reset-password`
- **User routes** (`ROLE_USER`):
  - Most application features (events, profile, club info)
- **Admin routes** (`ROLE_ADMIN`):
  - `/admin/*` - EasyAdmin dashboard
  - `/audit/*` - Audit logs

#### Group-Based Event Authorization
Events can be assigned to specific groups via `assignedGroups` (ManyToMany):
- **Open events**: No assigned groups → all licensees can participate
- **Restricted events**: Has assigned groups → only licensees in those groups can participate
- Authorization check: `EventHelper::canLicenseeParticipateInEvent()`
  - Returns true if event has no groups OR licensee is in at least one assigned group
- UI behavior: Disabled participation button with tooltip for unauthorized users
- Server-side validation: Form submission blocked with flash message

## Development Workflow

### Environment Setup

#### Docker Compose Services
- **app**: Main Symfony application (FrankenPHP)
- **messenger-async**: Async message consumer
- **scheduler-ffta-licensees**: Scheduled FFTA sync
- **database**: MariaDB 12.1
- **redis**: Rate limiter backend
- **adminer**: Database GUI (http://localhost:8081)

#### Database Access
- URL: http://localhost:8081
- Type: MySQL
- Server: `database`
- User: `symfony`
- Password: `ChangeMe`
- Database: `app`

#### Initial Setup
```bash
# Build and start containers
make start

# Install dependencies
make deps
# This runs:
# - composer install
# - npm ci
# - npm run dev

# Access shell
make shell        # As symfony user
make shell-root   # As root
```

### Running Commands

**CRITICAL**: All PHP/Symfony commands MUST run inside Docker container:
```bash
# Correct way:
docker compose exec -u symfony -w /app app bin/console <command>
docker compose exec -u symfony -w /app app composer <command>
docker compose exec -u symfony -w /app app vendor/bin/phpunit

# Or use Makefile shortcuts:
make shell  # Then run commands inside container
```

### Database Migrations

**Standard workflow**:
1. Modify entities (add/change properties, relationships)
2. Generate migration:
   ```bash
   docker compose exec -u symfony -w /app app bin/console make:migration
   ```
3. Review generated SQL in `migrations/Version*.php`
4. Apply migration:
   ```bash
   docker compose exec -u symfony -w /app app bin/console doctrine:migrations:migrate
   # Or use: make migratedb
   ```

**Important**: Always review migrations before applying in production!

**Branch workflow — one migration per branch**:

Goal: keep a single cumulative migration in a branch (not a chain of incremental ones) so merging to `main` produces a clean history.

When you need to regenerate a migration while one already exists in the branch:

```bash
# 1. Find migrations added in this branch (not on main)
BRANCH_MIGRATIONS=$(git diff main --name-only --diff-filter=A -- migrations/)

# 2. Roll back each one and delete the file
for f in $BRANCH_MIGRATIONS; do
    VERSION=$(basename "$f" .php)
    docker compose exec -u symfony -w /app app \
        bin/console doctrine:migrations:execute --down \
        "DoctrineMigrations\\${VERSION}" --no-interaction
    rm "$f"
done

# 3. Generate a fresh, complete migration
docker compose exec -u symfony -w /app app bin/console make:migration

# 4. Review the generated SQL, then apply
docker compose exec -u symfony -w /app app bin/console doctrine:migrations:migrate --no-interaction
```

Result: one migration file per branch, containing the full cumulative diff vs `main`.

### Code Quality Tools

#### PHP CS Fixer (Code Style)
- Standard: PSR-12
- Config: `.php-cs-fixer.php`
- Status: ✅ Works reliably
- Usage: Included in `make qa`

#### Rector (Automated Refactoring)
- Config: `rector.php`
- Status: ⚠️ May have Docker permission issues
- Usage: Included in `make qa`

#### PHPStan (Static Analysis)
- Config: `phpstan.dist.neon`
- Status: ⚠️ May have Docker permission issues
- Usage: Included in `make qa`

#### Run All QA Tools
```bash
make qa
# Runs: Rector, PHP CS Fixer, PHPStan
```

**Known issue**: Rector and PHPStan may fail with temp directory permissions. PHP CS Fixer works correctly.

### Testing

#### Running Tests

##### Full CI-equivalent run (recommended)

This mirrors what the GitHub Actions workflow does:

```bash
# Run the full test suite via docker-compose.test.yml
make test
```

`make test` runs `docker compose -p archery_test -f docker-compose.test.yml up --build --abort-on-container-exit --force-recreate`.  
The `sut` entrypoint inside the container automatically:
1. Runs `composer install` and `npm install && npm run dev`
2. Migrates the `app_test` database (`APP_ENV=test`)
3. Loads fixtures (`hautelook:fixtures:load`)
4. Executes `bin/phpunit` with clover/JUnit coverage output

##### Quick local loop (inside running dev container)

Use this when iterating quickly — no full image rebuild needed:

```bash
# 1. (Re)create and migrate the test database
docker compose exec -u symfony -w /app app sh -c '
  APP_ENV=test bin/console doctrine:database:drop --force --if-exists &&
  APP_ENV=test bin/console doctrine:database:create &&
  APP_ENV=test bin/console doctrine:migrations:migrate --no-interaction
'

# 2. Load fixtures into app_test
docker compose exec -u symfony -w /app app sh -c \
  'APP_ENV=test bin/console hautelook:fixtures:load --no-interaction'

# 3. Run all tests (excluding the "disabled" group)
docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled

# 4. Run a specific test file
docker compose exec -u symfony -w /app app bin/phpunit tests/Functional/Controller/ClubEquipmentControllerTest.php
```

##### Key test environment details
- **Config**: `.env.test` — database points to `app_test`, FriendlyCaptcha is bypassed, encryption key must be set
- **FriendlyCaptcha**: Disabled via `when@test` override in `config/services.yaml` (`$enabled: false`) — `verify()` always returns `true` in tests
- **Encryption**: Disabled via `config/packages/test/spec_shaper_encrypt.yaml`
- **DB transactions**: DAMA DoctrineTestBundle rolls back each test case automatically (configured in `config/packages/test/dama_doctrine_test_bundle.yaml`)
- **Fixtures**: YAML files in `fixtures/` loaded by `hautelook:fixtures:load`; must be reloaded whenever the schema changes

##### Encryption key for `.env.test`
`.env.test` must contain a valid `SPEC_SHAPER_ENCRYPT_KEY`. Generate one with:
```bash
docker compose exec -u symfony -w /app app bin/console encrypt:genkey
```

### Git Workflow

#### Pre-Commit Checklist
**CRITICAL**: Before committing, ALWAYS:
1. Run `make qa` until it passes (Rector, PHP CS Fixer, PHPStan)
2. Run tests: `docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled`
3. Fix any issues and repeat steps 1-2 until both pass

#### Commit Conventions
- **Atomic commits**: Group related changes by functionality
- **Commit messages**: Always use one-line messages with gitmoji for clarity
- **IMPORTANT**: Base commits ONLY on `git status`, NOT on conversation history
  - Review actual file changes before committing
  - Don't assume what changed from conversation context
  - Use `git status` and `git diff` to determine what to commit
- **Example commit sequence** (recent work):
  ```
  1. ✨ Add group-based event authorization
  2. ♻️ Create reusable participation modal component
  3. ✨ Implement event type-based participation choices
  4. ✅ Add authorization checks in EventController
  5. 💄 Update templates with disabled button and tooltips
  6. ♻️ Make target_type nullable for training events + migration
  7. ✨ Add Makefile start-fg target
  8. 🎨 Format code with PHP CS Fixer
  ```

#### Branching
- Main branch: `main` (or `master`)
- Feature branches: Descriptive names (e.g., `event-enhancement`)
- Push early and often

#### Using `gh` CLI with Body Content

**NEVER pass multi-line body content as a CLI argument** — shell escaping always fails. This applies to ALL `gh` commands that accept body content: `gh issue create`, `gh pr create`, `gh issue comment`, etc.

Always use the `create_file` tool to write the body to a temp file, then pass it with `--body-file`:

```bash
# 1. Use the create_file tool to write content to a temp file
#    (do NOT use cat > /tmp/... << 'EOF' ... EOF or --body "..." with multi-line strings)

# 2a. Create a PR
gh pr create \
  --title "✨ Your PR title" \
  --body-file /tmp/gh-body.md \
  --base main

# 2b. Create an issue
gh issue create \
  --title "✨ Your issue title" \
  --body-file /tmp/gh-body.md \
  --label "enhancement"

# 2c. Add a comment
gh issue comment 42 --body-file /tmp/gh-body.md

# 3. Clean up
rm /tmp/gh-body.md
```

### Makefile Reference

```makefile
make container        # Build Docker images
make start           # Start services (detached)
make start-fg        # Start services (foreground, see logs)
make stop            # Stop services
make shell           # Shell as symfony user
make shell-root      # Shell as root
make deps            # Install PHP + JS dependencies
make qa              # Run quality tools (Rector, PHP CS Fixer, PHPStan)
make migratedb       # Run database migrations
```

## Important Files & Directories

### Configuration
- `config/bundles.php` - Bundle registration
- `config/services.yaml` - Service container configuration
- `config/routes.yaml` - Global routes
- `config/routes/` - Route imports by bundle
- `config/packages/` - Bundle-specific configs
  - `security.yaml` - Authentication & authorization ⚠️ Critical
  - `doctrine.yaml` - ORM configuration
  - `framework.yaml` - Symfony framework config
  - `test/` - Test environment overrides
- `.env` - Environment variables (not in git)
- `.env.local` - Local overrides (not in git)
- `.env.test` - Test environment (in git)

### Source Code
- `src/Entity/` - Doctrine entities (domain model)
- `src/Controller/` - HTTP controllers
- `src/Form/` - Symfony form types
- `src/Repository/` - Doctrine repositories (custom queries)
- `src/Helper/` - Business logic services
- `src/DBAL/Types/` - Custom Doctrine enum types
- `src/Command/` - Console commands
- `src/Scrapper/` - FFTA web scraping
- `src/Security/` - Security-related services
- `src/EventListener/` - Event subscribers
- `src/Twig/` - Custom Twig extensions
- `src/DataFixtures/` - Test fixtures
- `src/ApiResource/` - API Platform resources
- `src/Factory/` - Object factories
- `src/Scheduler/` - Scheduled tasks
- `src/Service/` - Service classes
- `src/Validator/` - Custom validation constraints
- `Kernel.php` - Application kernel

### Templates
- `templates/base.html.twig` - Base layout with navbar, mobile tab bar, modals
- `templates/base_public.html.twig` - Public pages layout (login, registration)
- `templates/app_form_layout.html.twig` - Custom form theme (extends Bootstrap 5)
- `templates/_modal.html.twig` - Reusable modal component (Stimulus-powered)
- `templates/homepage/` - Dashboard
- `templates/event/` - Event views
  - `index.html.twig` - Calendar view
  - `show_contest.html.twig` - Contest detail
  - `show_training.html.twig` - Training detail
  - `show_default.html.twig` - Generic event detail
  - `_participation_modal.html.twig` - Reusable participation form modal ⭐
- `templates/licensee/` - Licensee views (profile, trombinoscope)
- `templates/club/` - Club information
- `templates/group/` - Group management
- `templates/club_application/` - Membership application views
- `templates/club_equipment/` - Equipment inventory and loan management
- `templates/email_notification/` - Notification email templates
- `templates/legal/` - Legal pages (CGU, privacy policy)
- `templates/licensee_management/` - Admin licensee management
- `templates/management/` - General management views
- `templates/user_management/` - Admin user management
- `templates/admin/` - EasyAdmin customizations

### Frontend Assets
- `assets/app.ts` - Main JavaScript entry (Font Awesome, Bootstrap, Stimulus)
- `assets/bootstrap.ts` - Stimulus app initialization
- `assets/controllers/` - Stimulus controllers
  - `modal_controller.ts` - Generic modal handler ⭐
- `assets/styles/` - SCSS files
  - `app.scss` - Main stylesheet (imports Bootstrap + custom)
  - `_mobile.scss` - Mobile-specific styles
  - `_events.scss` - Event-related styles
  - `_calendar.scss` - Calendar styles
  - `_event_participation.scss` - Participation state colors
  - `variables.scss` - Custom variables (colors, etc.)
- `assets/custom-svg-icons/` - Custom SVG icons
- `assets/images/` - Static images

### Build & Config
- `webpack.config.js` - Webpack Encore configuration
- `tsconfig.json` - TypeScript configuration
- `package.json` - NPM dependencies and scripts
- `composer.json` - PHP dependencies
- `symfony.lock` - Symfony Flex lock file

### Docker
- `docker-compose.yml` - Main compose file
- `docker-compose.override.yml` - Local overrides
- `docker-compose.test.yml` - Test environment
- `docker-compose.optimized.yml` - Production-optimized
- `docker/Dockerfile` - Main app image
- `docker/Dockerfile.optimized` - Production image
- `docker/config/` - Server configs (PHP, Caddy)
- `docker/build/` - Build scripts
- `docker/post_build/` - Post-build scripts

### Testing & QA
- `phpunit.xml.dist` - PHPUnit configuration
- `phpstan.dist.neon` - PHPStan configuration
- `rector.php` - Rector configuration
- `.php-cs-fixer.php` - PHP CS Fixer rules (implied)
- `tests/` - Test files
- `fixtures/` - Test data fixtures (YAML)

### Other
- `public/` - Web root
  - `index.php` - Front controller
  - `robots.txt` - Search engine directives ⭐
  - `sitemap.xml` - SEO sitemap (minimal) ⭐
  - `build/` - Compiled assets (generated by Encore)
- `migrations/` - Doctrine migrations
- `translations/` - Translation files
- `var/` - Cache, logs (not in git)
- `vendor/` - Composer dependencies (not in git)
- `node_modules/` - NPM dependencies (not in git)

## Business Logic Deep Dive

### Event Participation System

#### States & Labels
**EventParticipationStateType** enum with context-dependent labels:

| State | Contest Label | Training Label |
|-------|---------------|----------------|
| `NOT_GOING` | "Je n'y vais pas" | "Absent" |
| `INTERESTED` | "Intéressé" | _(not available)_ |
| `REGISTERED` | "Je suis inscrit" | "Présent" |

#### Form Rendering (`EventParticipationType`)
- **Option**: `is_contest` (boolean) determines field visibility
- **Contest-specific fields**:
  - Target type (required, not nullable)
  - Departure time
  - 3 participation choices (NOT_GOING, INTERESTED, REGISTERED)
- **Training-specific**:
  - 2 participation choices (NOT_GOING as "Absent", REGISTERED as "Présent")
  - No target type, no departure
- **Common fields**:
  - Activity (required, dropdown from LicenseActivityType)
  - Default activity: First activity from licensee's current license

#### Dynamic Defaults
Implemented in `EventHelper::licenseeParticipationToEvent()`:
1. If EventParticipation exists in DB → return it
2. If not exists → create virtual EventParticipation (not persisted):
   - Set event & participant
   - Set default activity from licensee's license
   - **For training events only**:
     - If licensee can participate (in assigned group) → set state to REGISTERED
     - If not authorized → leave state null
   - **For contests**: No default state (user must choose)

#### Authorization Flow
1. **UI Layer** (`show_*.html.twig`):
   ```twig
   {% set can_participate = eventHelper.canLicenseeParticipateInEvent(licensee, event) %}
   <button class="btn {{ can_participate ? 'btn-primary' : 'btn-secondary' }}"
           {{ not can_participate ? 'disabled' : '' }}
           data-bs-toggle="{{ can_participate ? 'modal' : '' }}"
           {{ not can_participate ? 'title="..." data-bs-toggle="tooltip"' : '' }}>
   ```
   - Button always visible
   - Disabled with tooltip if unauthorized

2. **Server Layer** (`EventController`):
   ```php
   if (!$eventHelper->canLicenseeParticipateInEvent($licensee, $event)) {
       $this->addFlash('danger', 'Vous ne pouvez pas vous inscrire à cet événement.');
       return $this->redirectToRoute('...');
   }
   ```
   - Validates authorization before persisting
   - Prevents direct POST attacks

3. **Business Logic** (`EventHelper::canLicenseeParticipateInEvent()`):
   ```php
   // If no groups assigned → open to all
   if ($event->getAssignedGroups()->isEmpty()) {
       return true;
   }
   // Check if licensee is in at least one assigned group
   foreach ($event->getAssignedGroups() as $eventGroup) {
       if ($licensee->getGroups()->contains($eventGroup)) {
           return true;
       }
   }
   return false;
   ```

#### Participant Lists
For training events, `EventHelper::getAllParticipantsForEvent()` returns:
- Existing EventParticipation from DB
- **PLUS** virtual EventParticipation for all group members (with REGISTERED default)
- Deduplication by licensee ID
- Used in templates to show "who's coming" with defaults

### FFTA Integration

#### Data Sources
- **Espace Dirigeant** (dirigeant.ffta.fr):
  - Club manager interface
  - Licensee data (name, birthdate, gender, member code, license info)
  - Requires club credentials stored in `Club` entity
- **Mon Espace** (monespace.ffta.fr):
  - Individual member interface
  - Profile pictures
  - Additional details

#### Synchronization Strategy
1. **FftaScrapper** uses Symfony HttpClient + BrowserKit
2. **Authentication**:
   - Logs in with club credentials
   - Maintains session cookies
   - Separate clients for "Espace Dirigeant" and "Mon Espace"
3. **Data scraping**:
   - Parses HTML tables with DomCrawler
   - Extracts JSON from AJAX responses
   - Downloads profile pictures as binary
4. **Entity creation/update**:
   - Matches by FFTA member code or ID
   - Updates existing or creates new Licensee
   - Updates License for current season
   - Uses `ObjectComparator` to detect changes
5. **Commands**:
   - `FftaSyncLicenseesCommand`: Bulk sync (all club members)
   - `FftaSyncLicenseeCommand`: Single licensee sync
   - `FftaFetchParticipatingEvent`: Import events
   - `FftaFindId`: Resolve FFTA ID from member code

#### Sync Triggers
- **Manual**: Admin button in UI
- **Scheduled**: `scheduler-ffta-licensees` service (cron expression)
- **Command**: Direct CLI invocation

### Season Management

#### Season Detection
- Current season determined by: `Season::seasonForDate($date)`
- Logic: Archery season typically starts Sept 1
  - If month >= September → current year
  - If month < September → previous year
- Example: 2025-11-04 → Season 2025

#### Season Selection
- User can switch seasons via `SeasonHelper`
- Stored in session
- Affects:
  - License display (only current season by default)
  - Event filtering
  - Trombinoscope (member directory)

### File Storage

#### Flysystem Configuration
- **Local dev**: Filesystem adapter
- **Production**: S3 adapter (async-aws)
- Adapters registered in `config/packages/flysystem.yaml`

#### Attachments
- **EventAttachment**: Event documents (rules, maps, etc.)
- **LicenseeAttachment**: Medical certificates, ID photos, etc.
  - Type categorized by `LicenseeAttachmentType`
- **PracticeAdviceAttachment**: Training resources
- VichUploaderBundle manages uploads
- Files served via temporary signed URLs (for S3)

### Email System

#### Mailer
- Symfony Mailer with:
  - Mailgun transport (production)
  - Scaleway transport (alternative)
  - Mailcatcher (dev, http://localhost:1080)
- Email verification: SymfonyCasts VerifyEmailBundle
- Password reset: SymfonyCasts ResetPasswordBundle

#### Email Templates
- Twig templates with Inky (responsive email markup)
- CSS inliner for email client compatibility
- Example: `templates/email/registration.html.twig`

> **Detailed coding patterns** for PHP, TypeScript, Twig, and testing are in the scoped instruction files:
> - PHP/Symfony → [`.github/instructions/backend.instructions.md`](.github/instructions/backend.instructions.md)
> - TypeScript/SCSS → [`.github/instructions/frontend.instructions.md`](.github/instructions/frontend.instructions.md)
> - Testing → [`.github/instructions/testing.instructions.md`](.github/instructions/testing.instructions.md)
> - Twig templates → [`.github/instructions/templates.instructions.md`](.github/instructions/templates.instructions.md)

## Troubleshooting

### Common Issues

#### Docker Permission Errors
- **Symptom**: "Permission denied" for tmp/ or cache/
- **Solution**: Run as `symfony` user, not root
- **Check**: `make shell` (uses `-u symfony` flag)

#### Asset Build Failures
- **Symptom**: Webpack compilation errors
- **Solution**: 
  ```bash
  rm -rf node_modules package-lock.json
  npm install
  npm run dev
  ```

#### Database Connection Refused
- **Symptom**: "Connection refused" to database
- **Check**: Services running (`docker compose ps`)
- **Solution**: `make start` and wait for healthcheck

#### FFTA Sync Failures
- **Symptom**: Scrapper errors, login failures
- **Check**: Club FFTA credentials in database
- **Debug**: Check `var/log/dev.log` for HTTP errors
- **Common**: FFTA site changes break scraper (HTML structure)

#### Migration Errors
- **Symptom**: "Table already exists" or "Column not found"
- **Check**: Current migration status
  ```bash
  bin/console doctrine:migrations:status
  ```
- **Solution**: Rollback or manually fix schema

#### Stimulus Controller Not Working
- **Symptom**: JavaScript errors, controllers not loading
- **Check**: 
  1. Controller registered in `assets/controllers.json`
  2. Controller class exported correctly
  3. Browser console for errors
- **Debug**: Check `data-controller` attribute in HTML

### Debugging Tools

#### Symfony Profiler
- Bottom toolbar in dev mode
- Click to see detailed request info
- Database queries, events, services, etc.

#### Logs
- `var/log/dev.log` - Development logs
- `var/log/prod.log` - Production logs
- Tail logs: `docker compose logs -f app`

#### Database GUI
- Adminer: http://localhost:8081
- View data, run queries, export/import

#### Email Testing
- Mailcatcher: http://localhost:1080
- Catches all outgoing emails
- View HTML/text versions

## Deployment Considerations

### Environment Variables
- **Required**:
  - `APP_ENV`: `prod`
  - `APP_SECRET`: Random string (32+ chars)
  - `DATABASE_URL`: PostgreSQL/MariaDB connection string
  - `MAILER_DSN`: Email transport (Mailgun/Scaleway)
  - `SPEC_SHAPER_ENCRYPT_KEY`: Encryption key for sensitive data
- **Optional**:
  - S3 credentials for file storage
  - Sentry DSN for error tracking
  - Discord webhook URL
  - Mapbox API key for geocoding

### Pre-Deployment Steps
1. Run tests: `bin/phpunit`
2. Run QA tools: `make qa`
3. Build production assets: `npm run build`
4. Clear cache: `bin/console cache:clear --env=prod`
5. Warm up cache: `bin/console cache:warmup --env=prod`
6. Run migrations: `bin/console doctrine:migrations:migrate --no-interaction`

### Docker Production
- Use `docker-compose.optimized.yml`
- Build with `Dockerfile.optimized`
- Enable OPcache
- Disable Xdebug
- Use Caddy/FrankenPHP for HTTP/2, HTTPS

### Monitoring
- Sentry for error tracking
- Grafana Faro for observability (web vitals, tracing)
- SonarCloud for code quality
- FOSSA for license compliance

## AI Agent Workflow Guidelines

### Standard Delivery Loop (Issue → PR)
Use this loop for every non-trivial change so work is traceable and reviewable.

1. Create or confirm the tracking issue (link external source such as Aikido/Sonar when relevant).
2. Sync from `main` and create a dedicated feature branch.
3. Implement the smallest complete fix.
4. Run required checks in Docker: `make qa` then `docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled`.
5. Commit atomically with a one-line gitmoji message.
6. Push branch and open PR linked to the issue.
7. Check CI status and investigate failed checks immediately.
8. Read PR comments (human + bot), apply focused fixes, and push.
9. If available, trigger Copilot review and address actionable feedback.
10. Repeat steps 7-9 until CI is green and review feedback is resolved.

### Test-Driven Development
- **ALWAYS run tests** after adding, updating, or removing features
- Execute full test suite: `bin/phpunit --exclude-group=disabled`
- For specific tests: `bin/phpunit path/to/TestFile.php`
- Verify tests pass before committing changes
- Update or add tests when modifying functionality

### Docker-First Execution
- **ALL Symfony/PHP commands MUST run inside Docker container**
- Correct pattern: `docker compose exec -u symfony -w /app app bin/console <command>`
- Never run PHP/Composer commands directly on host machine
- Use `make shell` for interactive container sessions

### Git Commit Conventions
- **Use one-line commit messages** with gitmoji for clarity
- **Group related changes** by functionality or logical units
- Examples:
  - `✨ Add group-based event authorization`
  - `♻️ Refactor participation modal component`
  - `🐛 Fix DAMA bundle SAVEPOINT conflict`
  - `🔥 Remove pre-registration functionality`
  - `✅ Update tests for PHPUnit 11 attributes`
- **Always base commits on `git status`**, not conversation history
- Review changed files before committing to ensure logical grouping
- Avoid mixing unrelated changes in single commit

### Code Quality Automation
- **Run ALL quality checks before committing:**
  ```bash
  make qa  # Runs Rector, PHP CS Fixer, PHPStan
  ```
- Or run individually inside container:
  ```bash
  vendor/bin/rector process src tests
  vendor/bin/php-cs-fixer fix
  vendor/bin/phpstan analyse
  ```
- Address all errors and warnings before pushing
- Quality checks are mandatory, not optional

### Documentation & Research
- **Ask for documentation URLs** when working with newer library versions
- Don't assume API compatibility across major versions
- Check official docs for breaking changes
- Example: "Can you provide the documentation URL for PHPUnit 11 migration guide?"

### Frontend Development - Font Awesome Icons
- **When adding icons to templates:**
  1. Add icon import in `assets/app.ts`: `import { faIconName } from '@fortawesome/...';`
  2. Add to library: `library.add(faIconName);`
  3. Rebuild JavaScript: `npm run dev`
- **When removing icons:** Remove from imports, library.add(), and rebuild
- Icons won't render without proper TypeScript registration
- Use Font Awesome 7+ naming conventions (camelCase in TypeScript)

### Admin & Club Admin Features
- **Default to frontend implementation**, not EasyAdmin pages
- Build custom controllers and templates using Bootstrap/Stimulus
- Only use EasyAdmin when explicitly requested by user
- Frontend features provide better UX and more flexibility
- EasyAdmin is for basic CRUD operations only

### Best Practices Enforcement
- **Always follow Symfony best practices:**
  - Use dependency injection (constructor injection preferred)
  - Type hint everything (strict_types=1)
  - Use Doctrine repositories for database queries
  - Never instantiate entities with `new` in controllers (use factories if needed)
  - Flash messages for user feedback
  - Form validation with constraints
- **PHP best practices:**
  - PSR-12 coding standard (enforced by PHP CS Fixer)
  - Return type declarations on all methods
  - Readonly properties where applicable (PHP 8.4+)
  - Null safety (avoid null where possible)

## Tips for AI Assistants

### Critical Rules
1. **Docker Context**: ALWAYS run commands inside container via `docker compose exec`
2. **File Reading**: Read large sections (50-100 lines) rather than many small reads
3. **Commit Messages**: One-line with gitmoji, grouped by functionality
4. **Bootstrap Classes**: Use existing utilities before creating custom CSS
5. **Entity Changes**: Always generate and review migrations
6. **Authorization**: Check group membership for event participation
7. **Form Themes**: Specify `bootstrap_5_layout.html.twig` for consistency
8. **FFTA Sync**: Scrapper is fragile, expect HTML changes to break it
9. **Enums**: Use DBAL types (e.g., `EventParticipationStateType`) for type safety
10. **Helpers**: Use service layer (EventHelper, LicenseHelper) for business logic

### Code Review Checklist
- [ ] Entities have proper Doctrine annotations/attributes
- [ ] Migrations generated and reviewed
- [ ] Authorization checks in controller and template
- [ ] Form validation and error handling
- [ ] Flash messages for user feedback
- [ ] Tooltips for disabled actions
- [ ] Responsive design (mobile and desktop)
- [ ] Icons properly sized and spaced
- [ ] Translations for user-facing text (if applicable)
- [ ] Tests updated or added
- [ ] No hardcoded values (use parameters or config)
- [ ] Proper use of Symfony services (no `new`)
- [ ] Error logging for debugging
- [ ] No repeated string literals (extract to `const` if used 3+ times)
- [ ] Methods have ≤ 3 `return` statements
- [ ] No commented-out code left in source
- [ ] TypeScript classes are named and use `readonly` on static properties

### Common Mistakes to Avoid
- ❌ Running PHP commands outside Docker container
- ❌ Committing without running tests first
- ❌ Committing without running code quality checks (make qa)
- ❌ Adding Font Awesome icons without updating TypeScript files
- ❌ Creating EasyAdmin pages when frontend features are needed
- ❌ Creating custom CSS when Bootstrap utility exists
- ❌ Forgetting to persist related entities (cascade persist errors)
- ❌ Using `|raw` filter without sanitization
- ❌ Hardcoding URLs instead of using `path()` function
- ❌ Creating migrations without reviewing SQL
- ❌ Mixing business logic in controllers (use helpers)
- ❌ Not checking authorization before sensitive operations
- ❌ Using `form_widget()` alone (lose labels and validation)
- ❌ Ignoring mobile responsiveness
- ❌ Making assumptions about library APIs without checking documentation

### SonarQube Code Quality Rules

SonarCloud runs on every push. Proactively follow rules to avoid new issues — full examples with code samples are in the scoped instruction files:
- PHP rules (S1192, S1142, S1854, S125) → [`.github/instructions/backend.instructions.md`](.github/instructions/backend.instructions.md)
- TypeScript rules (S4212, S3827, S6582) → [`.github/instructions/frontend.instructions.md`](.github/instructions/frontend.instructions.md)

### Project-Specific Vocabulary
- **Licensee**: Club member (archer)
- **License**: Annual membership for a season
- **Trombinoscope**: Member directory (French term)
- **FFTA**: Fédération Française de Tir à l'Arc (French Archery Federation)
- **Espace Dirigeant**: FFTA club manager portal
- **Mon Espace**: FFTA member portal
- **Season**: Archery year (Sept-Aug)
- **Practice level**: Beginner, Intermediate, Advanced
- **Age category**: Benjamins, Minimes, Cadets, Juniors, Seniors, etc.

## Resources

### Official Documentation
- **Symfony**: https://symfony.com/doc/7.4/index.html
- **Doctrine ORM**: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/
- **Doctrine DBAL**: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/
- **Twig**: https://twig.symfony.com/doc/3.x/
- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/
- **Font Awesome**: https://fontawesome.com/docs
- **Stimulus**: https://stimulus.hotwired.dev/
- **Webpack Encore**: https://symfony.com/doc/current/frontend.html
- **API Platform**: https://api-platform.com/docs/

### Symfony Bundles
- **EasyAdmin**: https://symfony.com/bundles/EasyAdminBundle/current/index.html
- **VichUploader**: https://github.com/dustin10/VichUploaderBundle
- **Flysystem**: https://github.com/thephpleague/flysystem-bundle
- **Fresh Doctrine Enum**: https://github.com/fre5h/DoctrineEnumBundle
- **Auditor**: https://github.com/DamienHarper/auditor-bundle
- **KnpTime**: https://github.com/KnpLabs/KnpTimeBundle
- **OAuth2 Client**: https://github.com/knpuniversity/oauth2-client-bundle
- **Reset Password**: https://github.com/SymfonyCasts/reset-password-bundle
- **Verify Email**: https://github.com/SymfonyCasts/verify-email-bundle

### Frontend Libraries
- **Chart.js**: https://www.chartjs.org/docs/latest/
- **Leaflet**: https://leafletjs.com/reference.html
- **PDF.js**: https://mozilla.github.io/pdf.js/
- **Axios**: https://axios-http.com/docs/intro

### Development Tools
- **Rector**: https://getrector.com/documentation
- **PHP CS Fixer**: https://cs.symfony.com/
- **PHPStan**: https://phpstan.org/user-guide/getting-started
- **Docker Compose**: https://docs.docker.com/compose/

### Related Projects
- **FFTA**: https://www.ffta.fr/
- **Les Archers de Bordeaux Guyenne**: https://archersdebordeaux-guyenne.com

---

**Last Updated**: March 8, 2026  
**Project Status**: Active Development  
**License**: AGPL v3  
**Maintainer**: dehy  
**GitHub**: https://github.com/dehy/archery-manager

---

> The remainder of this file (Font Awesome workflow, card patterns, Bootstrap utilities, Twig patterns) has been moved to the scoped instruction files in [`.github/instructions/`](.github/instructions/). This footer marks the end of AGENTS.md.
