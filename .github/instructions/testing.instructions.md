---
applyTo: "tests/**"
---

# Testing Instructions (PHPUnit / Symfony)

## Quick Local Loop

Use when iterating quickly — no image rebuild needed:

```bash
# 1. Recreate and migrate the test database
docker compose exec -u symfony -w /app app sh -c '
  APP_ENV=test bin/console doctrine:database:drop --force --if-exists &&
  APP_ENV=test bin/console doctrine:database:create &&
  APP_ENV=test bin/console doctrine:migrations:migrate --no-interaction
'

# 2. Load fixtures into app_test
docker compose exec -u symfony -w /app app sh -c \
  'APP_ENV=test bin/console hautelook:fixtures:load --no-interaction'

# 3. Run all tests (excluding disabled group)
docker compose exec -u symfony -w /app app bin/phpunit --exclude-group=disabled

# 4. Run a specific test file
docker compose exec -u symfony -w /app app bin/phpunit tests/Functional/Controller/ClubEquipmentControllerTest.php
```

## Full CI Run

Mirrors the GitHub Actions workflow exactly:

```bash
# Export Font Awesome token first (required to install Pro npm packages)
export $(grep FONTAWESOME_NPM_AUTH_TOKEN .env.local | xargs)

# Full build + test in Docker
make test
```

`make test` runs `docker compose -p archery_test -f docker-compose.test.yml up --build --abort-on-container-exit --force-recreate`.
The `sut` entrypoint: installs deps → migrates `app_test` → loads fixtures → runs `bin/phpunit`.

## Test Environment Details

| Aspect | Detail |
|--------|--------|
| Config | `.env.test` — database: `app_test` |
| DB isolation | DAMA DoctrineTestBundle auto-rolls back each test case |
| Encryption | Disabled via `config/packages/test/spec_shaper_encrypt.yaml` |
| FriendlyCaptcha | Bypassed: `$enabled: false` in `config/services.yaml` `when@test` — `verify()` always returns `true` |
| Fixtures | YAML files in `fixtures/`, loaded by `hautelook:fixtures:load` |

### Generate Encryption Key for `.env.test`

```bash
docker compose exec -u symfony -w /app app bin/console encrypt:genkey
```

## Test Structure

```
tests/
├── Functional/          # Full HTTP request/response tests (WebTestCase)
├── Integration/         # Service/helper tests with DB (KernelTestCase)
├── Unit/                # Pure unit tests (no container)
├── SecurityTrait.php    # Shared auth helpers
├── MakePropertyAccessibleTrait.php
└── bootstrap.php
```

- Mirror `src/` directory structure inside `tests/`
- Namespace pattern: `Tests\Functional\Controller\`, `Tests\Integration\Helper\`, etc.
- Extend `KernelTestCase` for integration tests, `WebTestCase` for functional tests

## Writing Tests

```php
namespace Tests\Integration\Helper;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventHelperTest extends KernelTestCase
{
    private EventHelper $eventHelper;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->eventHelper = static::getContainer()->get(EventHelper::class);
    }

    public function testCanLicenseeParticipateInEventWithNoGroups(): void
    {
        // ...
        self::assertTrue($result);
    }
}
```

## Critical Pattern: Cascade Persist

Always persist related entities before their dependents — never assume cascade:

```php
// ✅ Correct
$club = (new Club())->setName('Test Club');
$entityManager->persist($club);

$license = (new License())->setClub($club);
$entityManager->persist($license);
$entityManager->flush();

// ❌ Wrong — will fail if Club is not persisted first
$license = (new License())->setClub(new Club());
$entityManager->persist($license);
$entityManager->flush();
```

## Fixtures

YAML fixtures in `fixtures/` directory:

| File | Contents |
|------|----------|
| `club.yml` | Test clubs (LADB, LADG) |
| `user_admin.yml` | Admin user account |
| `licensee_ladb.yml`, `licensee_ladg.yml` | Archer profiles for two clubs |
| `group.yml` | Organizational groups |
| `training_ladb.yml`, `training_ladg.yml` | Training events |
| `contest.yml` | Contest events |

Reload fixtures whenever the schema changes (step 2 of quick local loop).
