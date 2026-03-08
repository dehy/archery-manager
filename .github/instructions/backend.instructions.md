---
applyTo: "**/*.php"
---

# Backend Coding Instructions (PHP / Symfony / Doctrine)

## PHP & Symfony Conventions

- Always add `declare(strict_types=1);` at the top of every PHP file
- Use **constructor injection** with `readonly` for services:
  ```php
  public function __construct(
      private readonly EventHelper $eventHelper,
      private readonly LicenseHelper $licenseHelper,
  ) {}
  ```
- Use **Doctrine attributes** (not XML/YAML/annotations) for ORM mapping:
  ```php
  #[ORM\Entity(repositoryClass: LicenseeRepository::class)]
  #[ORM\Table(name: 'licensee')]
  class Licensee { ... }
  ```
- Use **typed class constants**: `private const string TABLE_NAME = 'licensee';`
- Return type declarations on ALL methods
- Prefer `readonly` class properties where values never change after construction

## Entity Changes

After modifying any entity:
1. Generate migration: `docker compose exec -u symfony -w /app app bin/console make:migration`
2. Review the SQL in `migrations/Version*.php` before applying
3. Apply: `docker compose exec -u symfony -w /app app bin/console doctrine:migrations:migrate`
   Or: `make migratedb`

**Branch rule — one migration per branch.** If a migration was already generated in this branch, roll it back and replace it with a fresh cumulative one:
```bash
# Roll back + delete all branch-specific migrations
BRANCH_MIGRATIONS=$(git diff main --name-only --diff-filter=A -- migrations/)
for f in $BRANCH_MIGRATIONS; do
    VERSION=$(basename "$f" .php)
    docker compose exec -u symfony -w /app app \
        bin/console doctrine:migrations:execute --down \
        "DoctrineMigrations\\${VERSION}" --no-interaction
    rm "$f"
done
# Then re-generate and apply
docker compose exec -u symfony -w /app app bin/console make:migration
docker compose exec -u symfony -w /app app bin/console doctrine:migrations:migrate --no-interaction
```

## Doctrine ORM 3.x Patterns

```php
// Avoid N+1 — eager load with leftJoin + addSelect
$results = $this->createQueryBuilder('e')
    ->leftJoin('e.assignedGroups', 'g')
    ->addSelect('g')
    ->where('e.startsAt >= :start')
    ->setParameter('start', $startDate)
    ->orderBy('e.startsAt', 'ASC')
    ->getQuery()
    ->getResult();

// Always use parameter binding — never string concatenation in queries
->where('e.type = :type')
->setParameter('type', $type)
```

## DBAL Types (Enums)

All enums are custom Doctrine DBAL types in `src/DBAL/Types/`. Use them — never raw strings.

| Type | Values / Purpose |
|------|-----------------|
| `ArrowType` | Arrow categories |
| `BowType` | Recurve, Compound, Longbow, Barebow |
| `ClubApplicationStatusType` | PENDING, VALIDATED, WAITING_LIST, REJECTED, CANCELLED |
| `ClubEquipmentType` | Equipment type classification |
| `ContestType` | Competition formats |
| `DisciplineType` | Indoor, Outdoor, Field, 3D, etc. |
| `EventAttachmentType` | Event attachment categories |
| `EventParticipationStateType` | NOT_GOING, INTERESTED, REGISTERED |
| `EventType` | Event categories |
| `FletchingType` | Fletching configurations |
| `GenderType` | Male, Female, Other |
| `LicenseActivityType` | Target, Field, 3D, etc. |
| `LicenseAgeCategoryType` | Benjamins, Minimes, Cadets, Juniors, Seniors, etc. |
| `LicenseCategoryType` | Competition categories |
| `LicenseType` | Competitive vs recreational |
| `LicenseeAttachmentType` | Attachment categories |
| `PracticeAdviceAttachmentType` | Training advice attachment types |
| `PracticeLevelType` | Beginner, Intermediate, Advanced |
| `TargetTypeType` | 80cm, 122cm, etc. |
| `UserRoleType` | User roles |

## Domain Entity Quick-Reference

| Entity | Key Relationships | Notes |
|--------|------------------|-------|
| `User` | hasMany Licensees | Auth account (email-based) |
| `Licensee` | belongsTo User, hasMany Licenses, belongsToMany Groups | Archer profile; FFTA synced |
| `License` | belongsTo Licensee, Season, Club | Annual membership; unique per (licensee, season) |
| `Club` | hasMany Licenses, ClubEquipments | Stores FFTA credentials |
| `Season` | — | `Season::seasonForDate()` auto-detects current season |
| `Group` | belongsToMany Licensees, Events | Org unit for access control |
| `Event` (abstract) | hasMany EventParticipations, assignedGroups | ContestEvent / HobbyContestEvent / TrainingEvent / FreeTrainingEvent |
| `EventParticipation` | Licensee ↔ Event | state: NOT_GOING / INTERESTED / REGISTERED |
| `ConsentLog` | belongsTo User (nullable, CASCADE delete) | GDPR: services (JSON), action, policyVersion, ipAddressAnonymized |
| `ClubApplication` | Licensee → Club | Status: PENDING → VALIDATED / WAITING_LIST / REJECTED / CANCELLED |
| `ClubEquipment` | belongsTo Club, hasMany EquipmentLoans | quantity, purchasePrice, purchaseDate, bowType, brand, model |
| `EquipmentLoan` | ClubEquipment ↔ Licensee | startDate, returnDate, quantity, notes, createdBy |
| `Bow` / `Arrow` / `SightAdjustment` | belongsTo Licensee | Personal equipment tracking |
| `Result` | belongsTo ContestEvent, Licensee | Scores, rankings, FFTA sync status |

## Helper Services (Business Logic)

Never put business logic in controllers — use the service layer:

| Helper | Key Methods |
|--------|------------|
| `EventHelper` | `canLicenseeParticipateInEvent()`, `getAllParticipantsForEvent()`, `licenseeParticipationToEvent()` |
| `LicenseeHelper` | Current licensee from session, display name formatting |
| `LicenseHelper` | Current license detection, season-based queries |
| `SeasonHelper` | Current season detection, season selection (stored in session) |
| `ClubHelper` | Club-related utilities |
| `FftaHelper` | FFTA API integration helpers |
| `ResultHelper` | Results processing |
| `MapHelper` | Geocoding and mapping |

## Authorization

```php
// Group-based event access — always use EventHelper, never reimplement
if (!$eventHelper->canLicenseeParticipateInEvent($licensee, $event)) {
    $this->addFlash('danger', 'Vous ne pouvez pas vous inscrire à cet événement.');
    return $this->redirectToRoute('...');
}

// Role check — ROLE_ADMIN inherits ROLE_ALLOWED_TO_SWITCH; ROLE_USER = all authenticated
if (!$this->isGranted('ROLE_ADMIN')) {
    throw $this->createAccessDeniedException();
}
```

## Form Type Pattern

```php
class MyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isContest = $options['is_contest'];
        $builder->add('field', ChoiceType::class, [
            'choices' => $isContest ? [...] : [...],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MyEntity::class,
            'is_contest' => false,
        ]);
        $resolver->setAllowedTypes('is_contest', 'bool');
    }
}
```

## Flash Messages

```php
$this->addFlash('success', 'Opération réussie !');
$this->addFlash('danger', 'Une erreur est survenue.');
$this->addFlash('warning', 'Attention...');
```

## Validator Constraints

```php
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 255)]
private string $name;
```

## SonarQube PHP Rules (Mandatory)

**S1192 — Extract repeated string literals into constants**
Any string used 3+ times → `private const string`:
```php
// ❌ Bad
->setParameter('{{ score }}', $value)  // repeated 3 times

// ✅ Good
private const string PLACEHOLDER_SCORE = '{{ score }}';
->setParameter(self::PLACEHOLDER_SCORE, $value)
```

**S1142 — Limit method return points to ≤ 3**
```php
// ❌ Bad — 4 returns
public function verify(string $solution): bool {
    if (!$this->enabled) return true;
    if ('' === $solution) return false;
    if ($result->shouldAccept()) return true;
    return false;
}

// ✅ Good — 2 returns
public function verify(string $solution): bool {
    if (!$this->enabled) return true;
    $accepted = false;
    if ('' !== $solution) {
        $accepted = $result->shouldAccept();
    }
    return $accepted;
}
```

**S1854 — Remove useless statements**
```php
// ❌ Bad
new \DateTimeImmutable();  // result discarded — delete the line
```

**S125 — Remove commented-out code**
Delete commented-out code blocks entirely — do not leave them in source.

## Security Checklist

- **CSRF**: Enabled by default on all forms; `enable_csrf: true` in `security.yaml` for login
- **SQL injection**: Always use QueryBuilder with `setParameter()` — never concatenate user input
- **XSS**: Twig auto-escapes; use `|raw` only for trusted content with explicit sanitization
- **File uploads**: Validate MIME types and sizes; use VichUploaderBundle; store outside web root
- **Passwords**: `auto` algorithm in `security.yaml` (bcrypt/argon2); never log or display passwords
- **Sensitive data**: Use SpecShaper EncryptBundle for PII fields

## Critical Patterns

```php
// Always persist related entities before dependents (avoid cascade errors)
$entityManager->persist($club);
$license->setClub($club);
$entityManager->persist($license);
$entityManager->flush();

// Never use `new` for entities requiring services — use factories
// Never put DB queries in controllers — use repositories
```
