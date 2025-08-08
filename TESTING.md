# 🏹 Testing Strategy for API Platform Archery Manager

## Overview

This document outlines the comprehensive testing strategy for the Archery Manager application built with API Platform. Our testing approach follows the **Test Pyramid** principle with multiple layers of tests.

## Testing Pyramid

```
    🔺 Functional Tests (E2E Workflows)
   🔺🔺 Integration Tests (API Endpoints)  
  🔺🔺🔺 Unit Tests (Entity Logic)
```

## Test Structure

### 1. **Unit Tests** (`tests/Unit/`)
- **Purpose**: Test individual entity logic and business rules
- **Speed**: Very Fast ⚡
- **Files**: 
  - `EventTest.php` - Event entity behavior
  - `LicenseeTest.php` - Licensee entity behavior

### 2. **API Integration Tests** (`tests/Api/`)
- **Purpose**: Test API endpoints, CRUD operations, and HTTP responses
- **Speed**: Fast ⚡⚡
- **Files**:
  - `EventsTest.php` - Event CRUD operations
  - `LicenseesTest.php` - Licensee CRUD operations
  - `EventParticipationsTest.php` - Participation management
  - `ClubsTest.php` - Club management (existing)
  - `UsersTest.php` - User management (existing)

### 3. **Functional Tests** (`tests/Functional/`)
- **Purpose**: Test complete business workflows and user journeys
- **Speed**: Medium ⚡⚡⚡
- **Files**:
  - `EventWorkflowTest.php` - Complete event management workflow

### 4. **Validation Tests** (`tests/Validation/`)
- **Purpose**: Test entity validation rules and constraints
- **Speed**: Fast ⚡⚡
- **Files**:
  - `EntityValidationTest.php` - Validation rules testing

## Key Testing Features

### ✅ **What We Test**

1. **API Endpoints**
   - GET collections with pagination
   - POST create operations
   - GET individual resources
   - PATCH update operations
   - DELETE operations
   - Filtering and searching

2. **Entity Relationships**
   - One-to-Many relationships (Club → Events)
   - Many-to-One relationships (Event → Club)
   - Many-to-Many relationships (Group ↔ Licensee)
   - Bidirectional associations

3. **Business Workflows**
   - Event creation and management
   - Licensee registration
   - Event participation flow
   - Club event organization

4. **Data Validation**
   - Required field validation
   - Email format validation
   - Business rule validation

### 🛠 **Testing Tools Used**

- **API Platform Test Case**: For API endpoint testing
- **Zenstruck Foundry**: For factory-based test data generation
- **PHPUnit**: Core testing framework
- **Symfony Validator**: For validation testing

## Running Tests

### Quick Commands

```bash
# Run all tests
./api/run-tests.sh

# Run specific test suites
./api/run-tests.sh unit
./api/run-tests.sh api
./api/run-tests.sh functional
./api/run-tests.sh validation

# Run tests in Docker
docker compose exec php ./run-tests.sh

# Run with coverage
docker compose exec php vendor/bin/phpunit --coverage-html coverage/
```

### Manual PHPUnit Commands

```bash
# All tests
vendor/bin/phpunit

# Specific test class
vendor/bin/phpunit tests/Api/EventsTest.php

# Specific test method
vendor/bin/phpunit tests/Api/EventsTest.php::testCreateEvent

# With testdox output
vendor/bin/phpunit --testdox
```

## Test Data Management

### Factories (Foundry)
Tests use Foundry factories for consistent test data:

```php
// Create test data
$event = EventFactory::createOne(['name' => 'Test Event']);
$licensees = LicenseeFactory::createMany(5);

// Use in tests
$response = static::createClient()->request('GET', '/events/' . $event->getId());
```

### Database Reset
- Each test class uses `ResetDatabase` trait
- Database is automatically reset between tests
- No test pollution or dependencies

## Coverage Goals

| Test Type | Coverage Target | Current Status |
|-----------|----------------|----------------|
| Unit Tests | 90%+ | ✅ Implemented |
| API Tests | 95%+ | ✅ Implemented |
| Functional Tests | 80%+ | ✅ Implemented |
| Validation Tests | 100% | ✅ Implemented |

## Best Practices

### ✅ **Do's**

1. **Use descriptive test names**
   ```php
   public function testCreateEventWithValidData(): void
   public function testEventParticipationWorkflow(): void
   ```

2. **Follow AAA pattern** (Arrange, Act, Assert)
   ```php
   // Arrange
   $event = EventFactory::createOne();
   
   // Act
   $response = static::createClient()->request('GET', '/events/' . $event->getId());
   
   // Assert
   $this->assertResponseIsSuccessful();
   ```

3. **Test both positive and negative scenarios**
   ```php
   public function testValidEventCreation(): void
   public function testInvalidEventCreationMissingName(): void
   ```

4. **Use factories for test data**
   ```php
   $licensee = LicenseeFactory::createOne(['familyName' => 'Smith']);
   ```

### ❌ **Don'ts**

1. Don't hardcode IDs or rely on database state
2. Don't test implementation details, focus on behavior
3. Don't write overly complex test setups
4. Don't skip assertions or empty tests

## CI/CD Integration

Tests run automatically on:
- **GitHub Actions**: Every push and PR
- **Coverage Reports**: Uploaded to Codecov
- **Code Quality**: PHPStan analysis

## Adding New Tests

### For New Entities:
1. Create Unit test in `tests/Unit/NewEntityTest.php`
2. Create API test in `tests/Api/NewEntitiesTest.php`
3. Add validation tests in `tests/Validation/EntityValidationTest.php`
4. Create factory if needed

### For New Features:
1. Start with unit tests for business logic
2. Add API tests for endpoints
3. Create functional tests for workflows
4. Update validation tests if needed

## Example Test Structure

```php
<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\EntityFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class NewEntityTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        // Arrange
        EntityFactory::createMany(5);

        // Act
        $response = static::createClient()->request('GET', '/new_entities');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertCount(5, $response->toArray()['member']);
    }
}
```

## Troubleshooting

### Common Issues:

1. **Database Connection Issues**
   ```bash
   # Reset test database
   php bin/console doctrine:database:drop --force --env=test
   php bin/console doctrine:database:create --env=test
   php bin/console doctrine:migrations:migrate --no-interaction --env=test
   ```

2. **Factory Issues**
   ```bash
   # Clear cache
   php bin/console cache:clear --env=test
   ```

3. **Memory Issues**
   ```bash
   # Increase PHP memory limit
   php -d memory_limit=512M vendor/bin/phpunit
   ```

## Conclusion

This testing strategy ensures:
- **High confidence** in application stability
- **Fast feedback** during development
- **Regression prevention** for new features
- **Documentation** through test specifications

The tests serve as both **verification** of current functionality and **specification** for future development. Keep them simple, focused, and maintainable! 🎯
