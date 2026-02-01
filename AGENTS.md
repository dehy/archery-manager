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
- **Pre-Registration**: Public form for new members
- **Discord Integration**: Bot for club communication
- **Season Management**: Multi-season support with automatic season detection

## Technology Stack

### Backend
- **Framework**: Symfony 6.4.* (LTS)
- **PHP**: 8.3+ (with typed class constants, strict types)
- **Database**: MariaDB 10.11+ with Doctrine ORM 2.x
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
- **Icons**: Font Awesome 7.1+ Pro (Solid, Light, Regular, Duotone, Thin)
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
- **Package Manager**: Yarn 3.4+

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
- **database**: MariaDB 10.11.5
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
# - yarn install
# - yarn run encore dev

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
```bash
# All tests
docker compose exec -u symfony -w /app app bin/phpunit

# Specific test
docker compose exec -u symfony -w /app app bin/phpunit tests/Integration/Helper/FftaHelperTest.php
```

#### Test Environment
- Config: `.env.test`
- Encryption: Disabled via `config/packages/test/spec_shaper_encrypt.yaml`
  ```yaml
  spec_shaper_encrypt:
      is_disabled: true
  ```
- Fixtures: YAML files in `fixtures/` directory
  - `club.yml`, `licensee_*.yml`, `contest.yml`, `training_*.yml`, etc.
  - Loaded via Doctrine DataFixtures

#### Encryption Key for Tests
`.env.test` must include:
```env
SPEC_SHAPER_ENCRYPT_KEY=<generated-key>
```
Generate with:
```bash
docker compose exec -u symfony -w /app app bin/console encrypt:genkey
```

### Git Workflow

#### Commit Conventions
- **Atomic commits**: Group related changes by functionality
- **Commit messages**:
  - Simple changes: One-line message
  - Complex features: Multi-line with bullet points
- **Example commit sequence** (recent work):
  ```
  1. Fix Doctrine cascade persist in FftaHelperTest + add group authorization
  2. Create reusable participation modal component
  3. Implement event type-based participation choices
  4. Add authorization checks in EventController
  5. Update templates with disabled button and tooltips
  6. Make target_type nullable for training events + migration
  7. Add Makefile start-fg target
  8. Format code with PHP CS Fixer
  ```

#### Branching
- Main branch: `main` (or `master`)
- Feature branches: Descriptive names (e.g., `event-enhancement`)
- Push early and often

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
- `templates/pre_registration/` - Public pre-registration form
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

## UI/UX Patterns

### Bootstrap 5 Conventions
- **Layout**: Container, row, col-* grid
- **Spacing**: Margin (`m-*`, `mb-*`, `mt-*`) and padding (`p-*`) utilities
- **Flexbox**: `d-flex`, `justify-content-*`, `align-items-*`
- **Colors**: `text-*`, `bg-*`, `btn-*` (primary, secondary, success, danger, warning, info)
- **Display**: `d-none`, `d-block`, `d-md-block` (responsive)
- **Components**: `card`, `modal`, `dropdown`, `navbar`, `badge`, `alert`, `btn`, `form-control`

### Icon Usage
- **Font Awesome 6.5+**: Solid icons (`fa-solid fa-*`)
- Imported in `app.ts` and registered via `library.add()`
- SVG rendering via `dom.watch()` for performance
- Sizing: `fa-lg`, `fa-2x`, `fa-3x`, etc.
- Spacing: `me-2` (margin-end), `ms-2` (margin-start)
- **Recent pattern**: Inline icons with text using flexbox
  ```html
  <a class="btn d-flex align-items-center">
      <i class="fa-solid fa-user fa-lg me-2"></i>
      <span>Profile</span>
  </a>
  ```

### Modal Pattern
1. **Base modal** (`templates/_modal.html.twig`):
   - Generic reusable structure
   - Stimulus-controlled (`modal_controller.ts`)
2. **Trigger**:
   ```html
   <a href="{{ path('...') }}"
      data-action="modal#open"
      data-title="Modal Title"
      data-size="lg">
   ```
3. **Controller**:
   - Fetches content via AJAX
   - Injects into modal body
   - Handles form submission
   - Bootstrap Modal API for show/hide
4. **Event-specific**: `_participation_modal.html.twig`
   - Inline modal (not AJAX-loaded)
   - Conditional rendering based on `isContest`

### Form Themes
- Default: `bootstrap_5_layout.html.twig`
- Custom: `templates/app_form_layout.html.twig`
- Usage: `{% form_theme form 'bootstrap_5_layout.html.twig' %}`
- Rendering:
  ```twig
  {{ form_start(form) }}
  {{ form_row(form.field) }}  {# Label + widget + errors #}
  {{ form_widget(form.field) }}  {# Input only #}
  {{ form_label(form.field) }}   {# Label only #}
  {{ form_errors(form.field) }}  {# Errors only #}
  {{ form_end(form) }}
  ```

### Tooltips
- Bootstrap tooltips initialized in `app.ts`:
  ```js
  const tooltipTriggerList = Array.prototype.slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.forEach(
      (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
  );
  ```
- Usage:
  ```html
  <button data-bs-toggle="tooltip" title="Explanation">...</button>
  ```

### Disabled States with Tooltips
- Pattern for conveying why action is unavailable:
  ```html
  <button class="btn btn-secondary" disabled
          data-bs-toggle="tooltip"
          title="You cannot do this because...">
      Action
  </button>
  ```
- Use case: Event participation when user not in assigned group

### Responsive Design
- **Mobile-first**: Bootstrap breakpoints (sm, md, lg, xl)
- **Mobile tab bar**: Fixed bottom nav for small screens
  - Defined in `assets/styles/_mobile.scss`
  - Hidden on desktop (`d-md-none`)
- **Top navbar**: Fixed top nav for desktop
  - Hidden on mobile
- Body padding: `padding-top` and `padding-bottom` adjusted for fixed navs

### Custom SCSS Organization
- `assets/styles/app.scss` - Main import file
- `_variables.scss` - Custom Bootstrap variable overrides
- `_mobile.scss` - Mobile-specific styles
- `_events.scss` - Event-related components
- `_calendar.scss` - Calendar views
- `_event_participation.scss` - Participation state colors (extends Bootstrap bg-* classes)
- Pattern: Import Bootstrap functions/mixins first, then customize

## Common Development Patterns

### Template Includes
```twig
{# Reusable partial #}
{{ include('path/to/_partial.html.twig', {
    variable: value,
    anotherVar: entity
}) }}
```

### Conditional Rendering
```twig
{% if event is instanceof('App\\Entity\\ContestEvent') %}
    {# Contest-specific content #}
{% elseif event is instanceof('App\\Entity\\TrainingEvent') %}
    {# Training-specific content #}
{% else %}
    {# Default content #}
{% endif %}
```

### Authorization in Templates
```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin') }}">Admin Panel</a>
{% endif %}
```

### Doctrine Query Patterns
```php
// In repository
public function findUpcomingEvents(\DateTimeInterface $startDate): array
{
    return $this->createQueryBuilder('e')
        ->where('e.startsAt >= :start')
        ->setParameter('start', $startDate)
        ->orderBy('e.startsAt', 'ASC')
        ->getQuery()
        ->getResult();
}
```

### Form Type Patterns
```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isContest = $options['is_contest'] ?? false;
        
        $builder->add('participationState', ChoiceType::class, [
            'choices' => $isContest 
                ? [/* 3 choices */] 
                : [/* 2 choices */],
        ]);
        
        if ($isContest) {
            $builder->add('targetType', /* ... */);
        }
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventParticipation::class,
            'is_contest' => false,
        ]);
    }
}
```

### Helper Service Injection
```php
// In controller
public function __construct(
    private readonly EventHelper $eventHelper,
    private readonly LicenseHelper $licenseHelper
) {}

public function show(Event $event): Response
{
    $licensee = $this->licenseHelper->currentLicensee();
    $canParticipate = $this->eventHelper->canLicenseeParticipateInEvent($licensee, $event);
    // ...
}
```

### Flash Messages
```php
// In controller
$this->addFlash('success', 'Operation completed successfully!');
$this->addFlash('danger', 'An error occurred.');
$this->addFlash('warning', 'Please note...');
```

```twig
{# In template #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

## Testing Guidelines

### Writing Tests
- Place in `tests/` directory
- Mirror `src/` structure
- Use namespaces: `Tests\Integration\Helper\EventHelperTest`
- Extend: `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`

### Test Data
- Use DataFixtures for complex scenarios
- Use entity factories for simple object creation
- Clean up after tests (transactions auto-rollback)

### Critical Test Pattern (Cascade Persist)
```php
// Always persist related entities first!
$club = new Club();
$club->setName('Test Club');
// ... set required fields
$entityManager->persist($club);

$license = new License();
$license->setClub($club);  // Related entity already persisted
$entityManager->persist($license);
$entityManager->flush();
```

## Security Best Practices

### Input Validation
- Use Symfony Validator constraints on entities
- Example:
  ```php
  #[Assert\NotBlank]
  #[Assert\Length(min: 3, max: 255)]
  private string $name;
  ```

### CSRF Protection
- Enabled by default for forms
- `enable_csrf: true` in `security.yaml` for login

### SQL Injection Prevention
- Use Doctrine Query Builder with parameter binding
- Never concatenate user input into queries

### XSS Prevention
- Twig auto-escapes output by default
- Use `|raw` filter only for trusted content
- Sanitize HTML input if allowing rich text

### File Upload Security
- Validate MIME types
- Limit file sizes
- Use VichUploaderBundle for safe handling
- Store files outside web root or with restricted access

### Password Security
- Symfony password hasher uses bcrypt/argon2 by default
- `auto` algorithm selection in `security.yaml`
- Never log or display passwords

### Remember Me Security
- Secret key from `%kernel.secret%`
- 1 week lifetime (configurable)
- Always use HTTPS in production

## Performance Considerations

### Doctrine Optimization
- Use eager loading for related entities (avoid N+1):
  ```php
  $query = $repository->createQueryBuilder('e')
      ->leftJoin('e.assignedGroups', 'g')
      ->addSelect('g')
      ->getQuery();
  ```
- Use pagination for large result sets
- Index frequently queried columns

### Asset Optimization
- Production: `yarn run build` (minified, optimized)
- Development: `yarn run dev` (source maps, fast)
- Webpack Encore handles code splitting

### Caching
- Symfony cache pools available
- HTTP caching headers for static assets
- Doctrine query result cache for expensive queries

### Database
- Use connection pooling
- Optimize indexes for common queries
- Monitor slow query log

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
  rm -rf node_modules yarn.lock
  yarn install
  yarn run encore dev
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
3. Build production assets: `yarn run build`
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
  3. Rebuild JavaScript: `yarn run encore dev`
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
  - Readonly properties where applicable (PHP 8.3+)
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
- **Symfony**: https://symfony.com/doc/6.4/index.html
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

**Last Updated**: February 1, 2026  
**Project Status**: Active Development  
**License**: AGPL v3  
**Maintainer**: dehy  
**GitHub**: https://github.com/dehy/archery-manager

---

## Frontend Development Guidelines

### Font Awesome Icon Management

**CRITICAL WORKFLOW**: Every time you add a Font Awesome icon to a template, you MUST:

1. **Add icon to imports** in `assets/app.ts`:
   ```typescript
   import {
     // ... existing imports
     faYourNewIcon,  // Add here
   } from "@fortawesome/free-solid-svg-icons";
   ```

2. **Add icon to library.add()** in `assets/app.ts`:
   ```typescript
   library.add(
       // ... existing icons
       faYourNewIcon,  // Add here
   );
   ```

3. **Rebuild JavaScript assets**:
   ```bash
   docker compose exec -u symfony -w /app app yarn run encore dev
   ```

**Without rebuilding**, icons will not render! You'll see empty spaces where icons should be.

**Available Icon Packs**:
- `@fortawesome/pro-solid-svg-icons` - Solid weight icons (fa-solid)
- `@fortawesome/pro-regular-svg-icons` - Regular weight icons (fa-regular)
- `@fortawesome/pro-light-svg-icons` - Light weight icons (fa-light)
- `@fortawesome/pro-thin-svg-icons` - Thin weight icons (fa-thin)
- `@fortawesome/pro-duotone-svg-icons` - Duotone icons (fa-duotone)
- `@fortawesome/free-brands-svg-icons` - Brand logos (fa-brands, like Discord, Google)

**Template Usage**:
```twig
<em class="fa-solid fa-user me-2"></em>
<em class="fa-light fa-user me-2"></em>
<em class="fa-duotone fa-user me-2"></em>
<em class="fa-brands fa-discord me-2"></em>
```

**Icon Naming Convention**:
- Template: `fa-user-gear` → Import: `faUserGear` (camelCase)
- Template: `fa-arrow-right` → Import: `faArrowRight`
- Template: `fa-circle-info` → Import: `faCircleInfo`

### Card-Based UI Pattern

**Standard Card Structure**:
```twig
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <em class="fa-solid fa-icon me-2"></em>
            Card Title
        </h5>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
</div>
```

**Color Schemes**:
- `bg-primary` - Main actions, personal info
- `bg-success` - Positive content, results, licensees
- `bg-warning` - Admin actions, important notices
- `bg-info` - Help, informational content
- `bg-danger` - Errors, deletions
- `bg-ffta` - FFTA-branded sections (custom class)

**Card Spacing**:
- Use `mb-4` for card bottom margin
- Use `h-100` for equal height cards in rows
- Wrap cards in `col-lg-*` for responsive layout

### Empty States

**Always provide meaningful empty states**:
```twig
{% if items is empty %}
    <div class="text-center text-muted py-4">
        <em class="fa-solid fa-icon fa-3x mb-3"></em>
        <p class="mb-0">No items found</p>
    </div>
{% endif %}
```

### Icon Sizing and Spacing

**Common Patterns**:
- Inline with text: `me-2` (margin-end 2)
- Large standalone: `fa-lg`, `fa-2x`, `fa-3x`
- Fixed width: All icons are fixed-width by default in Font Awesome 7+ (no need for `fa-fw`)
- Alignment: `d-flex align-items-center` for parent

**Button Icons**:
```twig
<button class="btn btn-primary">
    <em class="fa-solid fa-save me-2"></em>
    Save
</button>
```

### Access Control in Templates

**Three-Tier Permission Pattern** (User/Licensee pages):
```twig
{% if is_granted('ROLE_ADMIN') or (app.user and app.user.id == user.id) %}
    {# Content visible to admin or owner #}
{% endif %}
```

**Club Admin Pattern**:
```twig
{% if is_granted('ROLE_ADMIN') or is_granted('ROLE_CLUB_ADMIN') %}
    {# Content visible to admin or club admin #}
{% endif %}
```

**Permission Checks in Controllers**:
```php
// Admin or self-access
if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
    throw $this->createAccessDeniedException();
}

// Club admin (check club membership)
$hasAccess = false;
foreach ($user->getLicensees() as $licensee) {
    if ($license = $licensee->getLicenseForSeason($currentSeason)) {
        if ($currentUserLicense && $currentUserLicense->getClub() === $license->getClub()) {
            $hasAccess = true;
            break;
        }
    }
}
```

### Responsive Design Patterns

**Column Breakpoints**:
```twig
<div class="row">
    <div class="col-lg-4 col-12">  {# 4 cols desktop, full mobile #}
        {# Sidebar content #}
    </div>
    <div class="col-lg-8 col-12">  {# 8 cols desktop, full mobile #}
        {# Main content #}
    </div>
</div>
```

**Button Groups**:
```twig
<div class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary">Action 1</button>
    <button class="btn btn-secondary">Action 2</button>
</div>
```

### User-Licensee Relationship Pattern

**Display User from Licensee**:
```twig
{# In licensee profile #}
<a href="{{ path('app_user_show', {'id': licensee.user.id}) }}">
    Voir le compte de {{ licensee.user.firstname }} {{ licensee.user.lastname }}
</a>
```

**Display Licensees from User**:
```twig
{# In user account #}
{% for licensee in user.licensees %}
    <a href="{{ path('app_licensee_profile', {'fftaCode': licensee.fftaMemberCode}) }}">
        {{ licensee_display_name(licensee) }}
    </a>
{% endfor %}
```

**Permission Context**:
- User profile: Broader account management, authentication, contact info
- Licensee profile: Archery-specific (licenses, results, equipment, FFTA integration)
- Always link between them for easy navigation

### Webpack Encore Commands

**Development Build** (with source maps):
```bash
docker compose exec -u symfony -w /app app yarn run encore dev
```

**Watch Mode** (auto-rebuild on changes):
```bash
docker compose exec -u symfony -w /app app yarn run encore dev --watch
```

**Production Build** (minified, optimized):
```bash
docker compose exec -u symfony -w /app app yarn run encore production
```

---

**Last Updated**: February 1, 2026  
**Project Status**: Active Development  
**License**: AGPL v3  
**Maintainer**: dehy  
**GitHub**: https://github.com/dehy/archery-manager
